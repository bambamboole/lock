<?php
declare(strict_types=1);

namespace App\Auth\Support;

use App\Shared\Auth\Attributes\AsPermission;
use App\Shared\Auth\Contracts\PermissionCatalog;
use App\Shared\Auth\Contracts\PermissionEnum;
use App\Shared\Auth\Data\PermissionDefinition;
use BackedEnum;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use RuntimeException;
use Spatie\Attributes\Attributes;
use Spatie\StructureDiscoverer\Discover;
use Spatie\StructureDiscoverer\Enums\Sort;
use Throwable;

final class PermissionRepository implements PermissionCatalog
{
    public const string CACHE_KEY = 'auth.permissions';

    /** @var list<PermissionDefinition>|null */
    private ?array $definitions = null;

    /** @var array<string, PermissionDefinition>|null */
    private ?array $byValue = null;

    /** @return list<PermissionDefinition> */
    public function realm(): array
    {
        return array_values(array_filter($this->all(), fn (PermissionDefinition $permission): bool => ! $permission->global));
    }

    /** @return list<PermissionDefinition> */
    public function global(): array
    {
        return array_values(array_filter($this->all(), fn (PermissionDefinition $permission): bool => $permission->global));
    }

    public function find(string $value): ?PermissionDefinition
    {
        if ($this->byValue === null) {
            $this->byValue = [];

            foreach ($this->all() as $permission) {
                $this->byValue[$permission->value] = $permission;
            }
        }

        return $this->byValue[$value] ?? null;
    }

    public function get(string $value): PermissionDefinition
    {
        $permission = $this->find($value);

        if ($permission === null) {
            throw new InvalidArgumentException("Unknown permission [{$value}].");
        }

        return $permission;
    }

    public function isGlobal(string $value): bool
    {
        return $this->find($value)->global ?? false;
    }

    /** @return list<string> */
    public function values(): array
    {
        return array_map(fn (PermissionDefinition $permission): string => $permission->value, $this->all());
    }

    public function rebuild(): int
    {
        Cache::forget(self::CACHE_KEY);
        $this->definitions = null;
        $this->byValue = null;

        return count($this->values());
    }

    /**
     * Runs on every request via the Gate::before hook, which also fires on keyless artisan
     * runs like `composer install`'s `package:discover` on a fresh checkout — before
     * `.env`/the database exist. The database-backed cache store throws in that case, so
     * it's caught rather than fataling package discovery itself.
     *
     * Only scalars go into the forever cache: serialized enum or DTO instances would
     * poison every deployed cache on a later class rename.
     *
     * @return list<PermissionDefinition>
     */
    private function all(): array
    {
        if ($this->definitions !== null) {
            return $this->definitions;
        }

        try {
            $cached = Cache::get(self::CACHE_KEY);
        } catch (Throwable) {
            $cached = null;
        }

        if (is_array($cached)) {
            $definitions = $this->hydrate($cached);

            if ($definitions !== null) {
                return $this->definitions = $definitions;
            }
        }

        $rows = $this->discover();

        try {
            Cache::forever(self::CACHE_KEY, $rows);
        } catch (Throwable) {
            // Nothing to cache into yet; the next request with a database tries again.
        }

        return $this->definitions = $this->hydrate($rows) ?? [];
    }

    /**
     * Sorted by file name because the discoverer otherwise yields the filesystem's own
     * readdir order, which differs per machine — the catalog order reaches the roles UI
     * and must not drift.
     *
     * @return list<array{enum: class-string<BackedEnum>, case: string, value: string, group: string, global: bool, description: string}>
     */
    private function discover(): array
    {
        $rows = [];

        /** @var list<class-string<BackedEnum>> $classes */
        $classes = new Discover(directories: [app_path()])
            ->enums()
            ->withAttribute(AsPermission::class)
            ->sortBy(Sort::Name)
            ->get();

        foreach ($classes as $enum) {
            $attribute = Attributes::get($enum, AsPermission::class);

            if (! $attribute instanceof AsPermission) {
                continue;
            }

            foreach ($enum::cases() as $case) {
                if (! is_string($case->value)) {
                    continue;
                }

                if (! $case instanceof PermissionEnum) {
                    throw new RuntimeException(sprintf('%s must implement %s.', $enum, PermissionEnum::class));
                }

                $rows[] = [
                    'enum' => $enum,
                    'case' => $case->name,
                    'value' => $case->value,
                    'group' => $attribute->label,
                    'global' => $attribute->global,
                    'description' => $case->getTranslationKey(),
                ];
            }
        }

        return $rows;
    }

    /**
     * Returns null when a cached row no longer matches a live enum case (stale cache
     * after a rename or deletion), so the caller falls back to fresh discovery.
     *
     * @param  array<int|string, mixed>  $rows
     * @return list<PermissionDefinition>|null
     */
    private function hydrate(array $rows): ?array
    {
        $definitions = [];

        foreach ($rows as $row) {
            if (! is_array($row) || ! is_string($row['enum'] ?? null) || ! is_string($row['case'] ?? null)) {
                return null;
            }

            if (! enum_exists($row['enum']) || ! defined("{$row['enum']}::{$row['case']}")) {
                return null;
            }

            $case = constant("{$row['enum']}::{$row['case']}");

            if (! $case instanceof PermissionEnum) {
                return null;
            }

            $definitions[] = new PermissionDefinition(
                case: $case,
                value: (string) $row['value'],
                group: (string) $row['group'],
                global: (bool) $row['global'],
                descriptionKey: (string) $row['description'],
            );
        }

        return $definitions;
    }
}
