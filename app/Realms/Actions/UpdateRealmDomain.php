<?php

declare(strict_types=1);

namespace App\Realms\Actions;

use App\Realms\Models\Realm;
use App\Shared\Audit\Audit;
use App\Shared\Realms\Contracts\UpdatesRealmDomain;
use App\Shared\Realms\Enums\RealmAuditEvent;
use App\Shared\Realms\Enums\RealmDomainStatus;
use App\Shared\Realms\Support\RealmDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use LogicException;

final class UpdateRealmDomain implements UpdatesRealmDomain
{
    public function handle(Realm $realm, string $domain): void
    {
        if ($realm->isMaster()) {
            throw new LogicException('The administration realm is served from APP_URL.');
        }

        $domain = strtolower(trim($domain));

        Validator::make(['domain' => $domain], ['domain' => RealmDomain::rules($realm)])->validate();

        if ($domain === $realm->domain) {
            return;
        }

        DB::transaction(function () use ($realm, $domain): void {
            $before = $realm->domain;

            $realm->forceFill([
                'domain' => $domain,
                'domain_status' => RealmDomainStatus::Pending,
                'domain_checked_at' => null,
                'domain_check_error' => null,
            ])->save();

            Audit::record(RealmAuditEvent::RealmUpdated, $realm, $realm, ['changes' => ['domain' => ['old' => $before, 'new' => $domain]]]);
        });
    }
}
