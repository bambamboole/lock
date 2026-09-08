<?php
declare(strict_types=1);

namespace Tests\Fixtures;

use App\Shared\Models\BusinessModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stand-in for the tenant-owned domain models this app will add. It ships none
 * yet, so BusinessModel would otherwise go unexercised.
 *
 * @property string $id
 * @property string $tenant_id
 * @property string $name
 */
#[Fillable(['name'])]
#[Table(name: 'scoped_records')]
final class ScopedRecord extends BusinessModel
{
    public static function createTable(): void
    {
        Schema::create('scoped_records', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });
    }
}
