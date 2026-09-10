<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oidc_password_history', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuidMorphs('authenticatable', 'oidc_password_history_authenticatable_index');
            $table->string('hash');
            $table->timestamp('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oidc_password_history');
    }
};
