<?php
declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesRealm;
use App\Shared\Realms\Contracts\UpdatesRealmDomain;
use App\Shared\Realms\Data\UpdateRealmData;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Support\RealmConfiguration;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelData\Optional;

final readonly class UpdateRealm implements UpdatesRealm
{
    public function __construct(private UpdatesRealmDomain $updateDomain) {}

    /**
     * The mirror of {@see CreateRealm}: everything the payload carries is
     * applied here, so no caller has to remember a second write.
     *
     * The submission is merged into the realm's settings DTO before anything is
     * compared, so a transport that types nothing — JSON with a quoted number,
     * an HTML form — is normalised to the type each setting holds rather than
     * leaving a string where an int belongs.
     */
    public function handle(Realm $realm, UpdateRealmData $data): Realm
    {
        DB::transaction(function () use ($realm, $data): void {
            $locked = Realm::query()->lockForUpdate()->findOrFail($realm->id);

            $this->applyConfiguration($locked, $data);

            if (! $data->domain instanceof Optional) {
                $this->updateDomain->handle($locked, $data->domain);
            }
        });

        return $realm->refresh();
    }

    /**
     * The domain carries its own audit entry and its own reset of the check
     * state, which is why it is not folded into these changes.
     */
    private function applyConfiguration(Realm $realm, UpdateRealmData $data): void
    {
        $submitted = [
            ...$data->name instanceof Optional ? [] : ['name' => $data->name],
            ...$data->settings instanceof Optional ? [] : $data->settings->submitted(),
        ];

        if ($submitted === []) {
            return;
        }

        $before = ['name' => $realm->name, ...$realm->configuration()];
        $settings = $realm->settings->merge(
            RealmConfiguration::validate([...$realm->configuration(), ...array_diff_key($submitted, ['name' => null])], $realm->slug),
        );
        $after = ['name' => $submitted['name'] ?? $realm->name, ...$settings->toArray()];

        $changes = [];

        foreach (array_keys($submitted) as $key) {
            if ($before[$key] !== $after[$key]) {
                $changes[$key] = ['old' => $before[$key], 'new' => $after[$key]];
            }
        }

        if ($changes === []) {
            return;
        }

        $realm->name = $after['name'];
        $realm->settings = $settings;
        $realm->save();

        Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, ['changes' => $changes]);
    }
}
