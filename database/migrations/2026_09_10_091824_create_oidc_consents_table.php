<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oidc_consents', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('realm_id')->default((string) config('oidc.realm', 'default'))->index();
            $table->uuid('user_id');
            $table->foreignUuid('client_id')->index();
            $table->string('resource');
            $table->json('scopes');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();

            $table->unique(['realm_id', 'user_id', 'client_id', 'resource']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oidc_consents');
    }
};
