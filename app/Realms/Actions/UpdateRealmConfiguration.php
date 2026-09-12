<?php

declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesRealmConfiguration;
use App\Shared\Realms\Data\RealmSetting;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Support\RealmConfiguration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class UpdateRealmConfiguration implements UpdatesRealmConfiguration
{
    /**
     * A submitted value is cast to the type its setting declares before it is
     * compared and stored: the stored configuration is typed, and a transport
     * that types nothing — JSON with a quoted number, an HTML form — would
     * otherwise leave a string where an int belongs.
     *
     * @param  array<string, mixed>  $data
     */
    public function handle(Realm $realm, array $data): void
    {
        DB::transaction(function () use ($realm, $data): void {
            $realm = Realm::query()->lockForUpdate()->findOrFail($realm->id);
            $before = ['name' => $realm->name, ...$realm->configuration()];
            $submitted = array_intersect_key($data, $before);
            $validated = Validator::make([...$before, ...$submitted], [
                ...RealmConfiguration::rules(),
                'trusted_clients.*' => [
                    ...RealmConfiguration::rules()['trusted_clients.*'],
                    Rule::exists('oidc_clients', 'client_id')->where('realm_id', $realm->slug)->where('revoked', 0),
                ],
            ])->setAttributeNames(RealmConfiguration::attributeNames())->validate();

            $changes = [];

            foreach (array_keys($submitted) as $key) {
                $setting = RealmConfiguration::setting($key);
                $value = $setting instanceof RealmSetting ? $setting->cast($validated[$key]) : $validated[$key];

                if ($before[$key] === $value) {
                    continue;
                }

                if ($key === 'name') {
                    $realm->update(['name' => $value]);
                } else {
                    $realm->settings->set('realm.'.$key, $value);
                }

                $changes[$key] = ['old' => $before[$key], 'new' => $value];
            }

            if ($changes !== []) {
                Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, ['changes' => $changes]);
            }
        });

        $realm->refresh();
    }
}
