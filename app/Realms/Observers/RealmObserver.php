<?php
declare(strict_types=1);

namespace App\Realms\Observers;

use App\Realms\Models\Realm;
use App\Realms\Models\Role;
use Illuminate\Support\Str;

final class RealmObserver
{
    public function creating(Realm $realm): void
    {
        if (empty($realm->slug)) {
            $realm->slug = $this->generateUniqueSlug($realm->name);
        }
    }

    public function updating(Realm $realm): void
    {
        if ($realm->isDirty('name')) {
            $realm->slug = $this->generateUniqueSlug($realm->name, $realm->id);
        }
    }

    public function created(Realm $realm): void
    {
        Role::seedPresetsFor($realm);
    }

    /**
     * Soft-deleted realms keep their slug, so the candidate query includes them —
     * a recycled slug would resolve two realms once the old one is restored.
     */
    private function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $defaultSlug = Str::slug($name);

        $query = Realm::withTrashed()
            ->where(function ($query) use ($defaultSlug) {
                $query->where('slug', $defaultSlug)
                    ->orWhere('slug', 'like', $defaultSlug.'-%');
            });

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $existingSlugs = $query->pluck('slug');

        $maxSuffix = $existingSlugs
            ->map(function (string $slug) use ($defaultSlug): ?int {
                if ($slug === $defaultSlug) {
                    return 0;
                } elseif (preg_match('/^'.preg_quote($defaultSlug, '/').'-(\d+)$/', $slug, $matches)) {
                    return (int) $matches[1];
                }

                return null;
            })
            ->filter(fn (?int $suffix) => $suffix !== null)
            ->max() ?? 0;

        return $existingSlugs->isEmpty()
            ? $defaultSlug
            : $defaultSlug.'-'.($maxSuffix + 1);
    }
}
