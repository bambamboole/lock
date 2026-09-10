<?php
declare(strict_types=1);

use App\Realms\Models\Realm;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            foreach ([config('realm.master_slug') => 'Admin', config('realm.default_slug') => 'Default'] as $slug => $name) {
                Realm::firstOrCreate(['slug' => $slug], ['name' => $name]);
            }
        });
    }
};
