<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Lock\Server\Clients\Models\Client;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('admin_events')
            ->where('subject_type', 'Bambamboole\\LaravelOidc\\Server\\Clients\\Models\\Client')
            ->update(['subject_type' => Client::class]);
    }
};
