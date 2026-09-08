<?php

declare(strict_types=1);

namespace App\Shared\Settings;

final class SettingsRegistry
{
    /**
     * @var array<string, SettingDefinition>
     */
    private array $definitions = [];

    public function define(string $key): SettingDefinition
    {
        return $this->definitions[$key] = new SettingDefinition($key);
    }

    public function find(string $key): ?SettingDefinition
    {
        return $this->definitions[$key] ?? null;
    }
}
