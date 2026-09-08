<?php

declare(strict_types=1);

namespace App\Shared\Support;

final readonly class BrandRewriter
{
    private const array FILES = [
        '.env',
        '.env.example',
        'composer.json',
        'README.md',
        'lang/en/welcome.php',
        'lang/de/welcome.php',
        'solo.yml',
        '.ai/guidelines/development.md',
        '.ai/skills/pr-review/SKILL.md',
        '.ai/skills/worktrees/SKILL.md',
    ];

    public function __construct(
        private string $basePath,
        private string $name,
        private string $slug,
        private string $domain,
    ) {}

    public function rewrite(): void
    {
        foreach (self::FILES as $relative) {
            $this->rewriteFile($relative);
        }

        $this->removeInstallHook();
    }

    /**
     * strtr replaces the longest matching key at each position and never
     * re-scans replaced text, so the full description, the composer name, and
     * the domain resolve before the bare `saas-starter-kit` slug.
     */
    private function rewriteFile(string $relative): void
    {
        $path = $this->basePath.'/'.$relative;

        if (! is_file($path)) {
            return;
        }

        $map = [
            'A batteries-included SaaS starter kit — Laravel + Inertia + React with Lattice server-driven UI.' => "The {$this->name} application, built with Laravel + Inertia + React and Lattice.",
            'bambamboole/saas-starter-kit' => "app/{$this->slug}",
            'saas-starter-kit.test' => $this->domain,
            'SaaS Starter Kit' => $this->name,
            'saas-starter-kit' => $this->slug,
        ];

        file_put_contents($path, strtr((string) file_get_contents($path), $map));
    }

    private function removeInstallHook(): void
    {
        $path = $this->basePath.'/composer.json';

        if (! is_file($path)) {
            return;
        }

        $json = json_decode((string) file_get_contents($path), true);
        $hook = $json['scripts']['post-create-project-cmd'] ?? [];

        $json['scripts']['post-create-project-cmd'] = array_values(array_filter(
            $hook,
            static fn (string $line): bool => ! str_contains($line, 'app:install'),
        ));

        file_put_contents(
            $path,
            json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n",
        );
    }
}
