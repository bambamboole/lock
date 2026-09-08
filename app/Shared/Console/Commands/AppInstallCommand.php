<?php

declare(strict_types=1);

namespace App\Shared\Console\Commands;

use App\Shared\Support\BrandRewriter;
use App\Shared\Support\InstallationPaths;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

use function Laravel\Prompts\text;

#[Description('Rebrand a fresh clone of the starter kit to your own application.')]
#[Signature('app:install {--name= : Application name} {--domain= : Local dev domain}')]
final class AppInstallCommand extends Command
{
    public function handle(InstallationPaths $paths): int
    {
        $nameOption = $this->option('name');
        $name = is_string($nameOption) && $nameOption !== '' ? $nameOption : text(
            label: 'Application name',
            placeholder: 'Acme',
            default: 'My App',
            required: true,
            validate: fn (string $value): ?string => self::validateName($value),
        );

        if ($error = self::validateName($name)) {
            $this->components->error($error);

            return self::FAILURE;
        }

        $slug = (string) Str::slug($name);

        $domainOption = $this->option('domain');
        $domain = is_string($domainOption) && $domainOption !== '' ? $domainOption : text(
            label: 'Local development domain',
            default: $slug.'.test',
            required: true,
        );

        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';
        $appUrl = $scheme.'://'.$domain;

        new BrandRewriter($paths->basePath(), $name, $slug, $domain)->rewrite();

        // BrandRewriter rewrote .env, but the running config still holds the old
        // values; refresh them so oidc:install-self provisions the new domain.
        config(['app.name' => $name, 'app.url' => $appUrl, 'oidc.issuer' => $appUrl]);

        if ($this->call('oidc:rotate-keys', ['--if-missing' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        if ($this->call('oidc:install-self', ['--name' => $name, '--force' => true]) !== self::SUCCESS) {
            return self::FAILURE;
        }

        $this->components->info("Rebranded to {$name} ({$domain}) and configured self-SSO.");

        return self::SUCCESS;
    }

    private static function validateName(string $name): ?string
    {
        if (str_contains($name, '"') || str_contains($name, '\\') || preg_match('/[\x00-\x1F\x7F]/', $name) === 1) {
            return 'Application name may not contain quotes or backslashes.';
        }

        return null;
    }
}
