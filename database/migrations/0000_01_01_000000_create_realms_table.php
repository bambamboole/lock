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
            // The slug doubles as the realm's DNS label, so it is bound by the
            // stricter of the two rules — lowercase, no leading/trailing dash.
            $table->string('slug')->unique();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('realms');
    }
};
