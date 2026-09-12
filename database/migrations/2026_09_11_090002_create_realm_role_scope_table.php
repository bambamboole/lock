<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('realm_role_scope', function (Blueprint $table) {
            $table->foreignUuid('realm_role_id')->constrained('realm_roles')->cascadeOnDelete();
            $table->foreignUuid('resource_scope_id')->constrained('resource_scopes')->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['realm_role_id', 'resource_scope_id']);
            $table->index('resource_scope_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realm_role_scope');
    }
};
