<?php
declare(strict_types=1);

use App\Realms\Models\Realm;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Realms created while they were still served below `/realms/{slug}` get
     * a subdomain of the master host, so they stay reachable once DNS and the
     * proxy follow. They start unchecked, like any newly assigned domain.
     */
    public function up(): void
    {
        Realm::query()
            ->whereNull('domain')
            ->where('slug', '!=', config('realm.master_slug'))
            ->each(fn (Realm $realm) => $realm->update(['domain' => $realm->slug.'.'.Realm::masterHost()]));
    }
};
