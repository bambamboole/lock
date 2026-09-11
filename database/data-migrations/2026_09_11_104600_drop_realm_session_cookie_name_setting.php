<?php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * A realm's own cookie name only mattered while realms shared one host;
     * on its own host every realm uses the application's session cookie.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'realm.session_cookie_name')->delete();
    }
};
