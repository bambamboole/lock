<?php

declare(strict_types=1);

namespace App\Shared\Settings\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property string $settingable_type
 * @property string $settingable_id
 * @property string $key
 * @property mixed $value
 */
#[Fillable(['key', 'value'])]
final class Setting extends Model
{
    use HasUuids;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function settingable(): MorphTo
    {
        return $this->morphTo();
    }
}
