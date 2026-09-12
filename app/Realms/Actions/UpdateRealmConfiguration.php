<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesRealmConfiguration;
use App\Shared\Realms\Data\UpdateRealmData;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Support\RealmConfiguration;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

final class UpdateRealmConfiguration implements UpdatesRealmConfiguration
{
    /**
     * The submission is merged into the realm's settings DTO before anything is
     * compared, so a transport that types nothing — JSON with a quoted number,
     * an HTML form — is normalised to the type each setting holds rather than
     * leaving a string where an int belongs.
     */
    public function handle(Realm $realm, UpdateRealmData $data): void
    {
        DB::transaction(function () use ($realm, $data): void {
            $realm = Realm::query()->lockForUpdate()->findOrFail($realm->id);
            $before = ['name' => $realm->name, ...$realm->configuration()];
            $submitted = [
                ...$data->name instanceof Optional ? [] : ['name' => $data->name],
                ...$data->settings instanceof Optional ? [] : $data->settings->submitted(),
            ];

            if ($submitted === []) {
                return;
            }

            $validated = RealmConfiguration::validate([...$before, ...$submitted], $realm->slug);
            $settings = $realm->settings->merge(array_intersect_key($validated, array_diff_key($submitted, ['name' => null])));
            $after = ['name' => $validated['name'], ...$settings->toArray()];

            $changes = [];

            foreach (array_keys($submitted) as $key) {
                if ($before[$key] === $after[$key]) {
                    continue;
                }

                $changes[$key] = ['old' => $before[$key], 'new' => $after[$key]];
            }

            if ($changes === []) {
                return;
            }

            if (array_key_exists('name', $changes)) {
                $realm->name = $validated['name'];
            }

            $realm->settings = $settings;
            $realm->save();

            Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, ['changes' => $changes]);
        });

        $realm->refresh();
    }
}
