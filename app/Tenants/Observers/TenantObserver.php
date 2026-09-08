<?php
declare(strict_types=1);

namespace App\Tenants\Observers;

use App\Tenants\Models\Role;
use App\Tenants\Models\Tenant;
use Illuminate\Support\Str;

final class TenantObserver
{
    public function creating(Tenant $tenant): void
    {
        if (empty($tenant->slug)) {
            $tenant->slug = $this->generateUniqueSlug($tenant->name);
        }

        $tenant->trial_ends_at ??= now()->addDays((int) config('billing.trial-days', 14));
    }

    public function updating(Tenant $tenant): void
    {
        if ($tenant->isDirty('name')) {
            $tenant->slug = $this->generateUniqueSlug($tenant->name, $tenant->id);
        }
    }

    public function created(Tenant $tenant): void
    {
        Role::seedPresetsFor($tenant);
    }

    /**
     * Soft-deleted tenants keep their slug, so the candidate query includes them —
     * a recycled slug would resolve two tenants once the old one is restored.
     */
    private function generateUniqueSlug(string $name, ?string $excludeId = null): string
    {
        $defaultSlug = Str::slug($name);

        $query = Tenant::withTrashed()
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
