<?php
declare(strict_types=1);

namespace App\Shared\Audit\Models;

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Illuminate\Database\Eloquent\Attributes\Appends;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Lang;
use Spatie\Activitylog\Models\Activity as BaseActivity;

/**
 * @property string|null $realm_id
 * @property-read string $causer_name
 */
#[Appends(['causer_name'])]
final class Activity extends BaseActivity
{
    /**
     * @return BelongsTo<Realm, $this>
     */
    public function realm(): BelongsTo
    {
        return $this->belongsTo(Realm::class);
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    #[Scope]
    protected function forRealm(Builder $query, Realm $realm): Builder
    {
        return $query->where('realm_id', $realm->id);
    }

    /**
     * @param  Builder<Activity>  $query
     * @return Builder<Activity>
     */
    #[Scope]
    protected function global(Builder $query): Builder
    {
        return $query->whereNull('realm_id')->where('log_name', 'admin');
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
