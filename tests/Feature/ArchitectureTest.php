<?php

declare(strict_types=1);
use App\Auth\Support\PermissionRepository;
use App\Billing\Http\Middleware\ShareSubscriptionCallout;
use App\Billing\Ui\Actions\Concerns\HandlesPaddleFailures;
use App\Tenants\Actions\RemoveMember;
use App\Tenants\Observers\TenantInvitationObserver;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Non-Ui domain slice namespaces derived from the directories that actually
 * exist, so a new domain or slice is covered without editing this file.
 * Ui slices (app/{Domain}/Ui/**) are the Lattice adapter layer and are excluded.
 *
 * @return array<int, string>
 */
function architectureDomainNamespaces(): array
{
    $slices = '{Actions,Concerns,Console,Contracts,Data,Database,Enums,Events,Http,Models,Notifications,Observers,Policies,Services,Settings,Support}';
    $appPath = dirname(__DIR__, 2).'/app';

    return collect([
        ...glob("{$appPath}/*/{$slices}", GLOB_ONLYDIR | GLOB_BRACE) ?: [],
        ...glob("{$appPath}/Shared/*/{$slices}", GLOB_ONLYDIR | GLOB_BRACE) ?: [],
    ])
        ->reject(fn (string $path): bool => str_contains($path, '/Ui/'))
        ->map(fn (string $path): string => 'App\\'.str_replace('/', '\\', Str::after($path, $appPath.'/')))
        ->unique()
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

    return collect(glob("{$appPath}/*/Ui", GLOB_ONLYDIR) ?: [])
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

    foreach (glob(dirname(__DIR__, 2).'/vendor/lattice-php/*/composer.json') ?: [] as $manifest) {
        /** @var array{autoload?: array{"psr-4"?: array<string, string>}} $package */
        $package = json_decode((string) file_get_contents($manifest), true);

        foreach ($package['autoload']['psr-4'] ?? [] as $prefix => $sourcePath) {
            $prefix = rtrim($prefix, '\\');

            if ($prefix !== 'Lattice') {
                $targets[] = $prefix;

                continue;
            }

            $sourceDir = dirname($manifest).'/'.trim($sourcePath, '/');

            foreach (glob("{$sourceDir}/*", GLOB_ONLYDIR) ?: [] as $directory) {
                $targets[] = 'Lattice\\'.basename($directory);
            }

            foreach (glob("{$sourceDir}/[A-Z]*.php") ?: [] as $file) {
                $targets[] = 'Lattice\\'.basename($file, '.php');
            }
        }
    }

    return collect($targets)->unique()->sort()->values()->all();
}

/**
 * Concrete Eloquent models whose primary-key strategy this app owns. A model
 * extending a vendor model (Passport tokens, activitylog activities) inherits
 * the vendor's key strategy and is skipped.
 *
 * @return list<class-string<Model>>
 */
function architectureDomainModels(): array
{
    $appPath = dirname(__DIR__, 2).'/app';
    $files = [
        ...glob("{$appPath}/*/Models/*.php") ?: [],
        ...glob("{$appPath}/Shared/*/Models/*.php") ?: [],
    ];

    $models = [];

    foreach ($files as $file) {
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
 * Lattice-as-framework, not UI building: two domain writes send an in-app
 * notification, and the billing middleware pushes a callout effect onto the
 * response. Additions here need a reason of the same kind.
 *
 * @return array<int, class-string>
 */
function architectureSanctionedLatticeConsumers(): array
{
    return [
        RemoveMember::class,
        TenantInvitationObserver::class,
        ShareSubscriptionCallout::class,
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

// One arch() per source namespace: `expect([A, B])->not->toUse(X)` passes as
// soon as ANY source lacks the dependency (pest-arch inverts "every source uses
// X"), so an array of sources silently checks nothing.
foreach (architectureDomainNamespaces() as $namespace) {
    arch("{$namespace} does not depend on the lattice ui layer")
        ->expect($namespace)
        ->not->toUse(architectureUiNamespaces())
        ->ignoring(architectureSanctionedLatticeConsumers());

    arch("{$namespace} does not depend on lattice")
        ->expect($namespace)
        ->not->toUse(architectureLatticeNamespaces())
        ->ignoring(architectureSanctionedLatticeConsumers());
}

arch('every billing action funnels paddle failures through the shared trait')
    ->expect('App\Billing\Ui\Actions')
    ->toUseTrait(HandlesPaddleFailures::class)
    ->ignoring('App\Billing\Ui\Actions\Concerns');

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
    foreach (app(PermissionRepository::class)->values() as $value) {
        expect($value)->toMatch('/^[a-z][a-z-]*(:[a-z][a-z-]*)+$/')
            ->and(Str::afterLast($value, ':'))->not->toBeIn(['read', 'list', 'show']);
    }
});
