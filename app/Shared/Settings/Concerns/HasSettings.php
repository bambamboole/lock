<?php

declare(strict_types=1);

namespace App\Shared\Settings\Concerns;

use App\Shared\Settings\Contracts\ProvidesSettings;
use App\Shared\Settings\Models\Setting;
use App\Shared\Settings\SettingsBag;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasSettings
{
    protected static function bootHasSettings(): void
    {
        static::deleting(function (Model&ProvidesSettings $model): void {
            $model->storedSettings()->delete();
        });
    }

    /**
     * @return MorphMany<Setting, $this>
     */
    public function storedSettings(): MorphMany
    {
        return $this->morphMany(Setting::class, 'settingable');
    }

    /**
     * @return array<string, mixed>
     */
    public function settingsRows(): array
    {
        return $this->storedSettings->pluck('value', 'key')->all();
    }

    public function forgetSettingsCache(): void
    {
        $this->unsetRelation('storedSettings');
    }

    /**
     * @return Attribute<SettingsBag, never>
     */
    protected function settings(): Attribute
    {
        return Attribute::get(fn (): SettingsBag => new SettingsBag($this))->withoutObjectCaching();
    }
}
