<?php
declare(strict_types=1);

use App\Realms\Models\Realm;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Realm::create(['slug' => config('realm.master_slug'), 'name' => 'Admin']);
    }
};
