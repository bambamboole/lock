<?php

declare(strict_types=1);

namespace App\Shared\Settings\Contracts;

use App\Shared\Settings\Models\Setting;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

interface ProvidesSettings
{
    /**
     * @return MorphMany<Setting, covariant Model>
     */
    public function storedSettings(): MorphMany;

    /**
     * @return array<string, mixed>
     */
    public function settingsRows(): array;

    public function forgetSettingsCache(): void;
}
