<?php

declare(strict_types=1);
use App\Admin\Models\Role;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmRole;
use App\Resources\Models\Resource;
use App\Resources\Models\ResourceScope;
use App\Shared\Admin\Enums\AdminPermission;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

use function Tests\Helpers\globPaths;

/**
 * Every child of a module except its Ui slice and its service provider, derived
 * from what exists so a new slice or root class is covered without editing
 * this file.
 *
 * @return array<int, string>
 */
function architectureLayerNamespaces(): array
{
    $appPath = dirname(__DIR__, 2).'/app';

    return collect(globPaths("{$appPath}/*/*"))
        ->reject(fn (string $path): bool => basename($path) === 'Ui' || str_ends_with($path, 'ServiceProvider.php'))
        ->map(fn (string $path): string => 'App\\'.str_replace(['/', '.php'], ['\\', ''], Str::after($path, $appPath.'/')))
        ->sort()
        ->values()
        ->all();
}

/**
 * @return array<int, string>
 */
function architectureUiNamespaces(): array
{
    $appPath = dirname(__DIR__, 2).'/app';

    return collect(globPaths("{$appPath}/*/Ui", GLOB_ONLYDIR))
        ->map(fn (string $path): string => 'App\\'.str_replace('/', '\\', Str::after($path, $appPath.'/')))
        ->sort()
        ->values()
        ->all();
}

/**
 * pest-arch silently matches nothing when the target is a bare top-level vendor
 * namespace ('Lattice'), so the forbidden targets are the concrete multi-segment
 * namespaces derived from the installed lattice-php packages.
 *
 * @return array<int, string>
 */
function architectureLatticeNamespaces(): array
{
    $targets = [];

    foreach (globPaths(dirname(__DIR__, 2).'/vendor/lattice-php/*/composer.json') as $manifest) {
        $package = json_decode((string) file_get_contents($manifest), true, flags: JSON_THROW_ON_ERROR);
        $autoload = is_array($package) && is_array($package['autoload']['psr-4'] ?? null) ? $package['autoload']['psr-4'] : [];

        foreach ($autoload as $prefix => $sourcePath) {
            $prefix = rtrim((string) $prefix, '\\');

            if ($prefix !== 'Lattice') {
                $targets[] = $prefix;

                continue;
            }

            $sourceDir = dirname($manifest).'/'.trim((string) $sourcePath, '/');

            foreach (globPaths("{$sourceDir}/*", GLOB_ONLYDIR) as $directory) {
                $targets[] = 'Lattice\\'.basename($directory);
            }

            foreach (globPaths("{$sourceDir}/[A-Z]*.php") as $file) {
                $targets[] = 'Lattice\\'.basename($file, '.php');
            }
        }
    }

    return collect($targets)->unique()->sort()->values()->all();
}

/**
 * A model extending a vendor model inherits the vendor's key strategy and is
 * skipped.
 *
 * @return list<class-string<Model>>
 */
function architectureDomainModels(): array
{
    $appPath = dirname(__DIR__, 2).'/app';
    $models = [];

    foreach ([...globPaths("{$appPath}/*/Models/*.php"), ...globPaths("{$appPath}/Shared/*/Models/*.php")] as $file) {
        $class = 'App\\'.str_replace('/', '\\', substr($file, strlen($appPath) + 1, -4));

        if (! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if (! $reflection->isAbstract() && ! architectureExtendsVendorModel($reflection)) {
            $models[] = $class;
        }
    }

    sort($models);

    return $models;
}

/**
 * @return array<int, string>
 */
function architectureDomains(): array
{
    $appPath = dirname(__DIR__, 2).'/app';

    return collect(globPaths("{$appPath}/*", GLOB_ONLYDIR))
        ->map(fn (string $path): string => basename($path))
        ->reject(fn (string $domain): bool => $domain === 'Shared')
        ->sort()
        ->values()
        ->all();
}

/**
 * Relations, route bindings and policies need the concrete Eloquent class, so
 * these may cross domain lines. pest-arch's ignoring() drops a class on both
 * sides, so the kernel models are also exempt as sources.
 *
 * @return array<int, class-string>
 */
function architectureSharedKernel(): array
{
    return [
        User::class,
        Realm::class,
        RealmRole::class,
        Role::class,
        Resource::class,
        ResourceScope::class,
    ];
}

/**
 * @param  ReflectionClass<Model>  $class
 */
function architectureExtendsVendorModel(ReflectionClass $class): bool
{
    for ($parent = $class->getParentClass(); $parent !== false && $parent->getName() !== Model::class; $parent = $parent->getParentClass()) {
        if (! str_starts_with($parent->getName(), 'App\\') && ! str_starts_with($parent->getName(), 'Illuminate\\')) {
            return true;
        }
    }

    return false;
}

it('finds the namespaces its layer rules check', function () {
    expect(architectureLayerNamespaces())->not->toBeEmpty()
        ->and(architectureUiNamespaces())->not->toBeEmpty()
        ->and(architectureLatticeNamespaces())->not->toBeEmpty()
        ->and(architectureDomains())->not->toBeEmpty();
});

// One arch() per source namespace: `expect([A, B])->not->toUse(X)` passes as
// soon as ANY source lacks the dependency (pest-arch inverts "every source uses
// X"), so an array of sources silently checks nothing.
foreach (architectureLayerNamespaces() as $namespace) {
    arch("{$namespace} does not depend on the lattice ui layer")
        ->expect($namespace)
        ->not->toUse(architectureUiNamespaces());

    arch("{$namespace} does not depend on lattice")
        ->expect($namespace)
        ->not->toUse(architectureLatticeNamespaces());
}

foreach (architectureDomains() as $domain) {
    $neighbours = collect(architectureDomains())
        ->reject(fn (string $other): bool => $other === $domain)
        ->map(fn (string $other): string => "App\\{$other}")
        ->values()
        ->all();

    arch("App\\{$domain} reaches other domains only through App\\Shared")
        ->expect("App\\{$domain}")
        ->not->toUse($neighbours)
        ->ignoring(architectureSharedKernel());
}

arch('App\Shared does not depend on a domain')
    ->expect('App\Shared')
    ->not->toUse(array_map(fn (string $domain): string => "App\\{$domain}", architectureDomains()))
    ->ignoring(architectureSharedKernel());

it('uses uuid primary keys on all domain models', function () {
    $models = architectureDomainModels();

    expect($models)->not->toBeEmpty();

    foreach ($models as $model) {
        expect(class_uses_recursive($model))
            ->toContain(HasUuids::class);
    }
});

it('keeps every concrete app class final', function () {
    $appPath = dirname(__DIR__, 2).'/app';
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($appPath, FilesystemIterator::SKIP_DOTS));

    $concrete = [];
    $notFinal = [];

    /** @var SplFileInfo $file */
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $class = 'App\\'.str_replace('/', '\\', substr($file->getPathname(), strlen($appPath) + 1, -4));

        if (! class_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isAbstract() || $reflection->isEnum()) {
            continue;
        }

        $concrete[] = $class;

        if (! $reflection->isFinal()) {
            $notFinal[] = $class;
        }
    }

    expect($concrete)->not->toBeEmpty()
        ->and($notFinal)->toBeEmpty();
});

it('keeps every permission value kebab-cased with a :view verb, never :read, so manage implies view', function () {
    foreach (array_column(AdminPermission::cases(), 'value') as $value) {
        expect($value)->toMatch('/^[a-z][a-z-]*(:[a-z][a-z-]*)+$/')
            ->and(Str::afterLast($value, ':'))->not->toBeIn(['read', 'list', 'show']);
    }
});
