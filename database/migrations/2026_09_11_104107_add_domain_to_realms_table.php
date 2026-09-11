<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every realm is served from a host of its own. The master realm's host is
     * the one in APP_URL and is never stored, so its `domain` stays null.
     */
    public function up(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->string('domain', 253)->nullable()->unique()->after('slug');
            $table->string('domain_status', 20)->default('pending')->after('domain');
            $table->timestamp('domain_checked_at')->nullable()->after('domain_status');
            $table->string('domain_check_error', 500)->nullable()->after('domain_checked_at');
        });
    }

    public function down(): void
    {
        Schema::table('realms', function (Blueprint $table) {
            $table->dropUnique(['domain']);
            $table->dropColumn(['domain', 'domain_status', 'domain_checked_at', 'domain_check_error']);
        });
    }
};
