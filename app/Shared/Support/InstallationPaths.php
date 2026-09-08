<?php

declare(strict_types=1);

namespace App\Shared\Support;

final readonly class InstallationPaths
{
    public function __construct(private ?string $basePath = null) {}

    public function basePath(): string
    {
        return $this->basePath ?? base_path();
    }
}
