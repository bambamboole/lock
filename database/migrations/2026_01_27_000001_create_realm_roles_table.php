<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // A null realm marks an instance-wide role; realm roles name theirs.
            $table->foreignUuid('realm_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('preset')->nullable();
            $table->timestamps();
            $table->unique(['realm_id', 'name']);
            $table->unique(['realm_id', 'preset']);
        });

        Schema::create('realm_invitations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 64)->unique();
            $table->foreignUuid('realm_id')->constrained()->cascadeOnDelete();
            $table->index('realm_id');
            $table->string('email');
            $table->foreignUuid('role_id')->nullable()->constrained('roles')->nullOnDelete();
            $table->foreignUuid('invited_by')->constrained('users')->cascadeOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realm_invitations');
        Schema::dropIfExists('roles');
    }
};
