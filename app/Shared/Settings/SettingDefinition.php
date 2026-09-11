<?php

declare(strict_types=1);

namespace App\Shared\Settings;

final class SettingDefinition
{
    private string $cast = 'string';

    private mixed $default = null;

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

    public function key(): string
    {
        return $this->key;
    }

    public function defaultValue(): mixed
    {
        return $this->default;
    }

    public function cast(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($this->cast) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'array' => (array) $value,
            default => (string) $value,
        };
    }
}
