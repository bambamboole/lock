<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('oidc_sessions', function (Blueprint $table): void {
            $table->renameColumn('logout_notified_at', 'logout_finished_at');
        });

        Schema::table('oidc_session_participants', function (Blueprint $table): void {
            $table->string('logout_status')->nullable();
            $table->timestamp('logout_attempted_at')->nullable();
        });
    }
};
