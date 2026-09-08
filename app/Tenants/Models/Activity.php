<?php
declare(strict_types=1);

namespace App\Tenants\Models;

use App\Auth\Models\User;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * @property string|null $tenant_id
 * @property-read string $causer_name
 */
#[Appends(['causer_name'])]
final class Activity extends BaseActivity
{
    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    #[Scope]
    protected function forTenant(Builder $query, Tenant $tenant): Builder
    {
        return $query->where('tenant_id', $tenant->id);
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    #[Scope]
    protected function global(Builder $query): Builder
    {
        return $query->whereNull('tenant_id')->where('log_name', 'admin');
    }

    /**
     * @return Attribute<string, never>
     */
    protected function causerName(): Attribute
    {
        return Attribute::make(get: function (): string {
            $causer = $this->causer;

            if ($causer instanceof User) {
                return $causer->name;
            }

            $actor = $this->properties?->get('actor');

            return is_string($actor) && $actor !== '' ? __('admin.activity.system-actor') : '—';
        });
    }

    /**
     * The description column stores a translation key; reading resolves it in
     * the viewer's locale. Raw text (or a key whose translation was removed)
     * falls through unchanged, so pre-existing rows keep rendering.
     *
     * @return Attribute<string|null, never>
     */
    protected function description(): Attribute
    {
        return Attribute::make(get: function (?string $value): ?string {
            if ($value === null || ! Lang::has($value)) {
                return $value;
            }

            $translated = __($value);

            return is_string($translated) ? $translated : $value;
        });
    }
}
