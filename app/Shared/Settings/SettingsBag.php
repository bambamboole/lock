<?php

declare(strict_types=1);

namespace App\Shared\Settings;

use App\Shared\Settings\Contracts\ProvidesSettings;

final class SettingsBag
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $loaded = null;

    public function __construct(private readonly ProvidesSettings $owner) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $definition = app(SettingsRegistry::class)->find($key);
        $rows = $this->rows();

        if (! array_key_exists($key, $rows)) {
            if ($default !== null) {
                return $default;
            }

            return $definition?->cast($definition->defaultValue());
        }

        return $definition !== null ? $definition->cast($rows[$key]) : $rows[$key];
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return $value === null ? $default : (string) $value;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : (bool) $value;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return $value === null ? $default : (int) $value;
    }

    /**
     * @param  array<mixed>  $default
     * @return array<mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key);

        return $value === null ? $default : (array) $value;
    }

    public function set(string $key, mixed $value): void
    {
        $definition = app(SettingsRegistry::class)->find($key);

        $this->owner->storedSettings()->updateOrCreate(['key' => $key], ['value' => $definition !== null ? $definition->cast($value) : $value]);
        $this->owner->forgetSettingsCache();
        $this->loaded = null;
    }

    /**
     * @return array<string, mixed>
     */
    private function rows(): array
    {
        $this->loaded ??= $this->owner->settingsRows();

        return $this->loaded;
    }
}
