<?php
declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Concerns\BelongsToRealm;
use App\Shared\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base class for realm-owned domain models: automatic realm scoping and
 * realm_id backfill, UUID keys, an audit trail, and soft deletes. A model that
 * also shows shared rows overrides the scope with
 * `#[ScopedBy(GlobalOrRealmScope::class)]`.
 */
abstract class BusinessModel extends Model
{
    use BelongsToRealm, HasUuids, RecordsActivity, SoftDeletes;
}
