<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Support\Facades\File;
use Lock\Server\Clients\Models\Client;

use function Tests\Helpers\readEnvironmentFile;

beforeEach(function () {
    $this->environmentPath = sys_get_temp_dir().'/seeder-'.bin2hex(random_bytes(4));
    File::ensureDirectoryExists($this->environmentPath);
    File::put($this->environmentPath.'/.env', '');
    app()->useEnvironmentPath($this->environmentPath);
    config(['lock.console_client' => ['id' => null, 'secret' => null]]);
});

afterEach(function () {
    File::deleteDirectory($this->environmentPath);
});

it('seeds the first administrator and gives a local setup console credentials of its own', function () {
    $this->seed();

    $user = User::query()->where('email', 'test@example.com')->sole();
    $realm = Realm::master();
    $client = Client::query()->where('provisioning_key', 'first-party')->sole();
    $environment = readEnvironmentFile(app()->environmentFilePath());

    expect($client->realm)->toBe($realm->slug)
        ->and($environment['LOCK_CONSOLE_CLIENT_ID'])->toBe($client->client_id)
        ->and($environment['LOCK_CONSOLE_CLIENT_SECRET'])->toBe($client->secret)
        ->and($realm->clients()->firstPartyClientId)->toBe($client->client_id)
        ->and($user->belongsToRealm($realm))->toBeTrue()
        ->and($user->can(Scope::RealmsWrite))->toBeTrue();
});

it('provisions the configured console client again after a fresh migration', function () {
    config(['lock.console_client' => ['id' => 'lock-console', 'secret' => 'configured-secret']]);

    $this->seed();

    $client = Client::query()->where('provisioning_key', 'first-party')->sole();

    expect($client->client_id)->toBe('lock-console')
        ->and($client->secret)->toBe('configured-secret')
        ->and(readEnvironmentFile(app()->environmentFilePath()))->toBeEmpty();
});
