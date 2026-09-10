<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oidc_clients', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('realm_id')->default((string) config('oidc.realm', 'default'))->index();
            $table->string('client_id');
            $table->nullableUuidMorphs('owner');
            $table->string('name');
            $table->string('secret')->nullable();
            $table->string('token_endpoint_auth_method');
            $table->json('redirect_uris');
            $table->json('post_logout_redirect_uris')->nullable();
            $table->json('grant_types');
            $table->json('default_scopes');
            $table->json('optional_scopes');
            $table->json('allowed_exchange_audiences')->nullable();
            $table->text('backchannel_logout_uri')->nullable();
            $table->boolean('backchannel_logout_session_required')->default(false);
            $table->boolean('consent_required')->default(true);
            $table->string('provisioning_key', 64)->nullable()->unique();
            $table->boolean('revoked')->default(false);
            $table->timestamps();

            $table->unique(['realm_id', 'client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oidc_clients');
    }
};
