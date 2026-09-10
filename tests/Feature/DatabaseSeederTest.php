<?php

declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;

use function Tests\Helpers\readEnvironmentFile;

beforeEach(function () {
    $basePath = sys_get_temp_dir().'/seeder-'.bin2hex(random_bytes(4));
    mkdir($basePath);
    file_put_contents($basePath.'/.env', '');
    app()->useEnvironmentPath($basePath);
});

it('seeds the first administrator and the application first-party client in the admin realm', function () {
    $this->seed();

    $user = User::query()->where('email', 'test@example.com')->sole();
    $realm = Realm::master();
    $client = Client::query()->where('provisioning_key', 'first-party')->sole();
    $environment = readEnvironmentFile(app()->environmentFilePath());

    expect($client->realm_id)->toBe($realm->slug)
        ->and($environment['OIDC_RP_CLIENT_ID'])->toBe($client->client_id)
        ->and($realm->clients()->firstPartyClientId)->toBe($client->client_id)
        ->and($user->belongsToRealm($realm))->toBeTrue()
        ->and($user->can(AdminPermission::ManageRealms))->toBeTrue();
});

it('re-provisions the self-SSO client when the configured one is gone from the database', function () {
    file_put_contents(app()->environmentFilePath(), implode("\n", [
        'OIDC_FIRST_PARTY_CLIENT=01wiped-by-fresh-migration',
        'OIDC_RP_CLIENT_ID=01wiped-by-fresh-migration',
        'OIDC_RP_CLIENT_SECRET=stale-secret',
        '',
    ]));
    Realm::master()->settings->set('realm.first_party_client_id', '01wiped-by-fresh-migration');

    $this->seed();

    $client = Client::query()->where('provisioning_key', 'first-party')->sole();
    $environment = readEnvironmentFile(app()->environmentFilePath());

    expect($environment['OIDC_FIRST_PARTY_CLIENT'])->toBe($client->client_id)
        ->and($environment['OIDC_RP_CLIENT_ID'])->toBe($client->client_id)
        ->and($environment['OIDC_RP_CLIENT_SECRET'])->not->toBe('stale-secret')
        ->and($environment['OIDC_RP_ISSUER'])->toBe(config('app.url').'/realms/'.Realm::master()->slug)
        ->and(Realm::master()->clients()->firstPartyClientId)->toBe($client->client_id);
});

it('leaves an intact self-SSO client untouched when re-seeding', function () {
    file_put_contents(app()->environmentFilePath(), "OIDC_RP_CLIENT_SECRET=existing-secret\n");

    $client = Realm::master()->runAsCurrent(fn (): Client => app(ClientRepository::class)
        ->createAuthorizationCodeGrantClient('First-party app', [config('app.url').'/login/callback']));
    $client->forceFill(['provisioning_key' => 'first-party'])->save();
    Realm::master()->settings->set('realm.first_party_client_id', $client->client_id);

    $this->seed();

    $environment = readEnvironmentFile(app()->environmentFilePath());

    expect(Client::query()->where('provisioning_key', 'first-party')->count())->toBe(1)
        ->and($environment['OIDC_RP_CLIENT_SECRET'])->toBe('existing-secret');
});
