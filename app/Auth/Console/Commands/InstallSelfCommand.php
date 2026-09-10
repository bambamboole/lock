<?php
declare(strict_types=1);

namespace App\Auth\Console\Commands;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Installation\Commands\InstallSelfCommand as BaseInstallSelfCommand;
use Bambamboole\LaravelOidc\Server\Shared\Installation\EnvironmentFile;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Illuminate\Support\Facades\DB;

final class InstallSelfCommand extends BaseInstallSelfCommand
{
    #[\Override]
    public function handle(): int
    {
        if (! $this->option('force') && $this->input->isInteractive()) {
            $appUrl = rtrim((string) config('app.url'), '/');

            if (! $this->confirm("Provision the first-party client and write self-SSO configuration to .env for {$appUrl}?")) {
                $this->info('Aborted.');

                return self::SUCCESS;
            }

            $this->input->setOption('force', true);
        }

        $status = parent::handle();

        if ($status === self::SUCCESS) {
            DB::transaction(function (): void {
                $realm = Realm::query()->where('slug', app(RealmResolver::class)->current()->id())->sole();
                $environment = app(EnvironmentFile::class);

                if ($realm->clients()->firstPartyClientId === null) {
                    $realm->settings->set('realm.first_party_trusted', true);
                }

                $realm->settings->set('realm.first_party_client_id', $environment->value('OIDC_FIRST_PARTY_CLIENT'));
            });
        }

        return $status;
    }
}
