<?php

declare(strict_types=1);

namespace App\Shared\Console\Commands;

use App\Realms\Models\Realm;
use App\Shared\Admin\Contracts\EnsuresAdministrator;
use App\Shared\Console\Enums\BootstrapOutcome;
use App\Shared\Realms\Contracts\ProvisionsConsoleClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Brings the console client and the first administrator in line with the
 * environment, so a container comes up fully configured without anything
 * being written to a `.env` it does not have. The environment is the source
 * of truth on every run; a variable left unset means unmanaged, which is what
 * keeps this safe to repeat on every deploy.
 */
#[Description('Reconcile the console client and the first administrator with the environment.')]
#[Signature('app:bootstrap')]
final class BootstrapCommand extends Command
{
    public function handle(ProvisionsConsoleClient $clients, EnsuresAdministrator $administrators): int
    {
        $realm = Realm::master();

        try {
            // One transaction across both items, and the report only after it
            // commits: a deploy step that fails leaves nothing behind and says
            // nothing it did not do.
            $outcomes = DB::transaction(fn (): array => [
                'console client' => $this->provisionClient($clients, $realm),
                'administrator' => $this->ensureAdministrator($administrators),
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->validator->errors()->all() as $message) {
                $this->components->error($message);
            }

            return self::FAILURE;
        } catch (RuntimeException $exception) {
            $this->components->error($exception->getMessage());

            return self::FAILURE;
        }

        foreach ($outcomes as $item => $outcome) {
            $this->report($item, $outcome);
        }

        return self::SUCCESS;
    }

    private function provisionClient(ProvisionsConsoleClient $clients, Realm $realm): ?BootstrapOutcome
    {
        $clientId = $this->configured('oidc.clients.first_party.client_id');
        $clientSecret = $this->configured('oidc.first_party_secret');
        $appUrl = rtrim($this->configured('app.url'), '/');

        if ($clientId === '' || $clientSecret === '') {
            return null;
        }

        if ($appUrl === '') {
            throw new RuntimeException('APP_URL must be set before the console client can be provisioned.');
        }

        return $clients->handle(
            realm: $realm,
            name: $this->configured('app.name') ?: 'Lock',
            clientId: $clientId,
            clientSecret: $clientSecret,
            trusted: (bool) config('oidc.clients.first_party.trusted', false),
            redirectUris: [$appUrl.'/login/callback', ...$this->provisionList('redirect_uris')],
            postLogoutRedirectUris: [$appUrl, ...$this->provisionList('post_logout_redirect_uris')],
        );
    }

    private function ensureAdministrator(EnsuresAdministrator $administrators): ?BootstrapOutcome
    {
        $email = $this->configured('lock.admin.email');
        $password = $this->configured('lock.admin.password');

        if ($email === '') {
            return null;
        }

        return $administrators->handle(
            $email,
            $this->configured('lock.admin.name') ?: 'Administrator',
            $password === '' ? null : $password,
        );
    }

    private function report(string $item, ?BootstrapOutcome $outcome): void
    {
        $this->components->twoColumnDetail($item, $outcome instanceof BootstrapOutcome ? $outcome->value : '<fg=gray>skipped (unset)</>');
    }

    private function configured(string $key): string
    {
        $value = config($key);

        return is_string($value) ? trim($value) : '';
    }

    /**
     * The extra URIs `oidc:install-self` layers on top of the APP_URL-derived
     * defaults, so both paths provision the same client.
     *
     * @return list<string>
     */
    private function provisionList(string $key): array
    {
        return array_values(array_filter(
            array_map(
                fn (mixed $value): string => is_string($value) ? trim($value) : '',
                (array) config("oidc.clients.first_party.provision.{$key}", []),
            ),
            fn (string $value): bool => $value !== '',
        ));
    }
}
