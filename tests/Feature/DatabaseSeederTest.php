<?php

declare(strict_types=1);

use Bambamboole\LaravelOidc\Server\Clients\Client;
use Bambamboole\LaravelOidc\Server\Clients\ClientRepository;

use function Tests\Helpers\readEnvironmentFile;

it('does not provision the self-SSO client outside the local environment', function () {
    $this->seed();

    expect(Client::query()->where('provisioning_key', 'first-party')->exists())->toBeFalse();
});

it('re-provisions the self-SSO client when the configured one is gone from the database', function () {
    $basePath = sys_get_temp_dir().'/seeder-'.bin2hex(random_bytes(4));
    mkdir($basePath);
    file_put_contents($basePath.'/.env', implode("\n", [
        'OIDC_FIRST_PARTY_CLIENT=01wiped-by-fresh-migration',
        'OIDC_RP_CLIENT_ID=01wiped-by-fresh-migration',
        'OIDC_RP_CLIENT_SECRET=stale-secret',
        '',
    ]));
    app()->useEnvironmentPath($basePath);
    app()['env'] = 'local';
    config(['oidc.first_party.client_id' => '01wiped-by-fresh-migration']);

    $this->seed();

    $client = Client::query()->where('provisioning_key', 'first-party')->sole();
    $environment = readEnvironmentFile($basePath.'/.env');

    expect($environment['OIDC_FIRST_PARTY_CLIENT'])->toBe((string) $client->id)
        ->and($environment['OIDC_RP_CLIENT_ID'])->toBe((string) $client->id)
        ->and($environment['OIDC_RP_CLIENT_SECRET'])->not->toBe('stale-secret');
});

it('leaves an intact self-SSO client untouched when re-seeding locally', function () {
    $basePath = sys_get_temp_dir().'/seeder-'.bin2hex(random_bytes(4));
    mkdir($basePath);
    file_put_contents($basePath.'/.env', "OIDC_RP_CLIENT_SECRET=existing-secret\n");
    app()->useEnvironmentPath($basePath);
    app()['env'] = 'local';

    $client = app(ClientRepository::class)
        ->createAuthorizationCodeGrantClient('First-party app', [config('app.url').'/login/callback']);
    $client->forceFill(['provisioning_key' => 'first-party'])->save();
    config(['oidc.first_party.client_id' => $client->client_id]);

    $this->seed();

    $environment = readEnvironmentFile($basePath.'/.env');

    expect(Client::query()->where('provisioning_key', 'first-party')->count())->toBe(1)
        ->and($environment['OIDC_RP_CLIENT_SECRET'])->toBe('existing-secret');
});
