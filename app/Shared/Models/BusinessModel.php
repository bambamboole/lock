<?php
declare(strict_types=1);

namespace App\Shared\Models;

use App\Shared\Concerns\BelongsToTenant;
use App\Shared\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Base class for tenant-owned domain models: automatic tenant scoping and
 * tenant_id backfill, UUID keys, an audit trail, and soft deletes. A model that
 * also shows shared rows overrides the scope with
 * `#[ScopedBy(GlobalOrTenantScope::class)]`.
 */
abstract class BusinessModel extends Model
{
    use BelongsToTenant, HasUuids, RecordsActivity, SoftDeletes;
}
