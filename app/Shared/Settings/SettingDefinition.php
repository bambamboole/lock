<?php

declare(strict_types=1);

namespace App\Shared\Settings;

final class SettingDefinition
{
    private string $cast = 'string';

    private mixed $default = null;

    private bool $encrypted = false;

    public function __construct(private readonly string $key) {}

    public function string(): self
    {
        $this->cast = 'string';

        return $this;
    }

    public function boolean(): self
    {
        $this->cast = 'boolean';

        return $this;
    }

    public function integer(): self
    {
        $this->cast = 'integer';

        return $this;
    }

    public function float(): self
    {
        $this->cast = 'float';

        return $this;
    }

    public function array(): self
    {
        $this->cast = 'array';

        return $this;
    }

    public function default(mixed $default): self
    {
        $this->default = $default;

        return $this;
    }

    public function encrypted(bool $encrypted = true): self
    {
        $this->encrypted = $encrypted;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function isEncrypted(): bool
    {
        return $this->encrypted;
    }

    public function defaultValue(): mixed
    {
        return $this->default;
    }

    public function cast(mixed $value): mixed
    {
        return $this->applyCast($value);
    }

    public function prepare(mixed $value): mixed
    {
        return $this->applyCast($value);
    }

    private function applyCast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->cast) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'float' => (float) $value,
            'array' => (array) $value,
            default => (string) $value,
        };
    }
}
