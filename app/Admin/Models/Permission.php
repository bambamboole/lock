<?php
declare(strict_types=1);

namespace App\Admin\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $key
 */
#[Fillable(['key'])]
final class Permission extends Model
{
    use HasUuids;
}
