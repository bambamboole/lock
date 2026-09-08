<?php

declare(strict_types=1);

use App\Shared\Support\InstallationPaths;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;

use function Tests\Helpers\readEnvironmentFile;

/**
 * Scoped to the worker process so a parallel run never deletes another
 * worker's fixtures when it drains this directory in afterEach.
 */
function installFixtureRoot(): string
{
    return sys_get_temp_dir().'/app-install-fixtures-'.getmypid();
}

function createInstallFixture(): string
{
    $basePath = installFixtureRoot().'/'.bin2hex(random_bytes(4));
    mkdir($basePath, 0777, true);

    file_put_contents($basePath.'/.env', implode("\n", [
        'APP_NAME="SaaS Starter Kit"',
        'APP_KEY=',
        'APP_URL=http://saas-starter-kit.test',
        'OIDC_FIRST_PARTY_CLIENT=',
        'OIDC_FIRST_PARTY_TRUSTED=false',
        'OIDC_RP_CLIENT_ID=',
        'OIDC_RP_CLIENT_SECRET=',
        '',
    ]));
    file_put_contents($basePath.'/.env.example', "APP_URL=http://saas-starter-kit.test\n");
    file_put_contents($basePath.'/composer.json', json_encode([
        'name' => 'bambamboole/saas-starter-kit',
        'description' => 'A batteries-included SaaS starter kit — Laravel + Inertia + React with Lattice server-driven UI.',
        'scripts' => [
            'post-create-project-cmd' => [
                '@php artisan key:generate --ansi',
                '@php artisan app:install',
            ],
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n");
    mkdir($basePath.'/lang/en', 0777, true);
    file_put_contents($basePath.'/lang/en/welcome.php', "<?php\nreturn ['heading' => 'Welcome to SaaS Starter Kit'];\n");

    return $basePath;
}

function useInstallFixture(string $basePath): void
{
    // BrandRewriter targets the fixture; oidc:install-self reads/writes the
    // app's environment file, so point both at the temp dir. The app URL mimics
    // a fresh install booted from the pre-rewrite .env.
    app()->instance(InstallationPaths::class, new InstallationPaths($basePath));
    app()->useEnvironmentPath($basePath);
    // Mimic a fresh install: no first-party client is configured yet (the test
    // env otherwise inherits the dev .env's client id).
    config(['app.url' => 'http://saas-starter-kit.test', 'oidc.first_party.client_id' => null]);
}

afterEach(function () {
    File::deleteDirectory(installFixtureRoot());
});

it('rebrands and configures self-sso', function () {
    $basePath = createInstallFixture();
    useInstallFixture($basePath);

    $this->artisan('app:install', ['--name' => 'Acme', '--domain' => 'acme.test'])
        ->assertSuccessful();

    $client = Client::query()->where('oidc_provisioning_key', 'first-party')->sole();
    $environment = readEnvironmentFile($basePath.'/.env');
    $composer = json_decode((string) file_get_contents($basePath.'/composer.json'), true);

    expect($client->name)->toBe('Acme')
        ->and($client->redirect_uris)->toBe(['http://acme.test/login/callback'])
        ->and($environment['OIDC_FIRST_PARTY_CLIENT'])->toBe((string) $client->id)
        ->and($environment['OIDC_FIRST_PARTY_TRUSTED'])->toBe('true')
        ->and($environment['OIDC_RP_CLIENT_ID'])->toBe((string) $client->id)
        ->and(Hash::check($environment['OIDC_RP_CLIENT_SECRET'], (string) $client->getRawOriginal('secret')))->toBeTrue()
        ->and(file_get_contents($basePath.'/.env'))->toContain('acme.test')
        ->and($composer['description'])->toBe('The Acme application, built with Laravel + Inertia + React and Lattice.')
        ->and($composer['scripts']['post-create-project-cmd'])->toBe(['@php artisan key:generate --ansi'])
        ->and(file_get_contents($basePath.'/lang/en/welcome.php'))->toContain('Welcome to Acme');
});

it('adopts the existing client on a second run without duplicating it', function () {
    $basePath = createInstallFixture();
    useInstallFixture($basePath);

    $this->artisan('app:install', ['--name' => 'Acme', '--domain' => 'acme.test'])->assertSuccessful();

    $first = readEnvironmentFile($basePath.'/.env');

    $this->artisan('app:install', ['--name' => 'Acme', '--domain' => 'acme.test'])->assertSuccessful();

    $second = readEnvironmentFile($basePath.'/.env');

    expect(Client::query()->where('oidc_provisioning_key', 'first-party')->count())->toBe(1)
        ->and($second['OIDC_FIRST_PARTY_CLIENT'])->toBe($first['OIDC_FIRST_PARTY_CLIENT'])
        ->and($second['OIDC_RP_CLIENT_SECRET'])->toBe($first['OIDC_RP_CLIENT_SECRET']);
});

it('rejects an unsafe --name and leaves composer.json untouched', function () {
    $basePath = createInstallFixture();
    useInstallFixture($basePath);

    $before = (string) file_get_contents($basePath.'/composer.json');

    $this->artisan('app:install', ['--name' => 'Bad"Name', '--domain' => 'x.test'])
        ->assertExitCode(1);

    expect(file_get_contents($basePath.'/composer.json'))->toBe($before)
        ->and(json_decode($before, true))->not->toBeNull();
});
