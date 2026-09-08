<?php

declare(strict_types=1);

namespace App\Shared\Settings;

use App\Shared\Settings\Contracts\ProvidesSettings;
use Illuminate\Support\Facades\Crypt;

final class SettingsBag
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $loaded = null;

    public function __construct(private readonly ProvidesSettings $owner) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $definition = app(SettingsRegistry::class)->find($key);
        $rows = $this->rows();

        if (! array_key_exists($key, $rows)) {
            if ($default !== null) {
                return $default;
            }

            return $definition?->cast($definition->defaultValue());
        }

        $raw = $rows[$key];

        if ($definition !== null && $definition->isEncrypted()) {
            $raw = json_decode(Crypt::decryptString($raw), true);
        }

        return $definition !== null ? $definition->cast($raw) : $raw;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key);

        return $value === null ? $default : (string) $value;
    }

    public function boolean(string $key, bool $default = false): bool
    {
        $value = $this->get($key);

        return $value === null ? $default : (bool) $value;
    }

    public function integer(string $key, int $default = 0): int
    {
        $value = $this->get($key);

        return $value === null ? $default : (int) $value;
    }

    public function float(string $key, float $default = 0.0): float
    {
        $value = $this->get($key);

        return $value === null ? $default : (float) $value;
    }

    /**
     * @param  array<mixed>  $default
     * @return array<mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key);

        return $value === null ? $default : (array) $value;
    }

    public function set(string $key, mixed $value): void
    {
        $definition = app(SettingsRegistry::class)->find($key);
        $stored = $definition !== null ? $definition->prepare($value) : $value;

        if ($definition !== null && $definition->isEncrypted()) {
            $stored = Crypt::encryptString((string) json_encode($stored));
        }

        $this->owner->storedSettings()->updateOrCreate(['key' => $key], ['value' => $stored]);
        $this->owner->forgetSettingsCache();
        $this->loaded = null;
    }

    public function forget(string $key): void
    {
        $this->owner->storedSettings()->where('key', $key)->delete();
        $this->owner->forgetSettingsCache();
        $this->loaded = null;
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        $out = [];

        foreach (array_keys($this->rows()) as $key) {
            $out[$key] = $this->get($key);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function rows(): array
    {
        $this->loaded ??= $this->owner->settingsRows();

        return $this->loaded;
    }
}
