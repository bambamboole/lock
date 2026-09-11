<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Realms are the identity boundary: a user belongs to exactly one, and
     * every other identity record hangs off it. This runs before the users
     * table so `users.realm_id` can be a plain foreign key.
     */
    public function up(): void
    {
        Schema::create('realms', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            // laravel-oidc keys every row it stores for the realm by the slug.
            $table->string('slug')->unique();
            // The host the realm is served from. The master realm is served
            // from APP_URL's host, so its row carries none.
            $table->string('domain', 253)->nullable()->unique();
            $table->string('domain_status', 20)->default('pending');
            $table->timestamp('domain_checked_at')->nullable();
            $table->string('domain_check_error', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realms');
    }
};
