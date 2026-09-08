<?php
declare(strict_types=1);

namespace Tests\Fixtures;

use App\Shared\Concerns\BelongsToRealmOrGlobal;
use App\Shared\Database\Scopes\GlobalOrRealmScope;
use App\Shared\Models\BusinessModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A BusinessModel that also carries shared rows, so the documented scope
 * override is exercised.
 *
 * @property string $id
 * @property string|null $realm_id
 * @property string $name
 */
#[Fillable(['name', 'realm_id'])]
#[ScopedBy(GlobalOrRealmScope::class)]
#[Table(name: 'shared_records')]
final class SharedRecord extends BusinessModel
{
    use BelongsToRealmOrGlobal;

    public static function createTable(): void
    {
        Schema::create('shared_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('realm_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
