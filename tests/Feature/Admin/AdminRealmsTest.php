<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Admin\Ui\Forms\CreateRealmForm;
use App\Admin\Ui\Forms\DeleteRealmForm;
use App\Admin\Ui\Forms\RealmSettingForm;
use App\Admin\Ui\Forms\UpdateRealmForm;
use App\Admin\Ui\Tables\AdminRealmsTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;

use function Tests\Helpers\globalAdmin;
use function Tests\Helpers\realmSetting;
use function Tests\Helpers\renderedTabKeys;

beforeEach(function () {
    (new GlobalRoleSeeder)->run();
});

test('every admin can list and open realms', function (string $roleName) {
    $realm = Realm::factory()->create();

    $this->actingAs(globalAdmin($roleName));
    $this->loadTable(AdminRealmsTable::class)->assertOk();
    $this->get('/admin/realms')->assertOk();
    $this->get("/admin/realms/{$realm->slug}/settings")->assertOk();
})->with(['support admin' => GlobalRoleSeeder::SUPPORT, 'super admin' => GlobalRoleSeeder::SUPER_ADMIN]);

test('a realm user reaches neither the realms list nor a realm', function () {
    $realm = Realm::factory()->create();

    $this->actingAs(User::factory()->create());
    $this->loadDeniedTable(AdminRealmsTable::class)->assertForbidden();
    $this->get('/admin/realms')->assertForbidden();
    $this->get("/admin/realms/{$realm->slug}/settings")->assertForbidden();
});

test('only realms-manage admins see the editing tabs, and the admin realm has no danger tab', function () {
    $realm = Realm::factory()->create();

    $support = $this->actingAs(globalAdmin(GlobalRoleSeeder::SUPPORT))->get("/admin/realms/{$realm->slug}/settings")->assertOk();
    $superAdmin = $this->actingAs(globalAdmin())->get("/admin/realms/{$realm->slug}/settings")->assertOk();
    $master = $this->get('/admin/realms/'.Realm::master()->slug.'/settings')->assertOk();

    expect(renderedTabKeys($support))->toBe(['general'])
        ->and(renderedTabKeys($superAdmin))->toBe(['general', 'tokens', 'sessions', 'mfa', 'passwords', 'clients', 'danger'])
        ->and(renderedTabKeys($master))->toBe(['general', 'tokens', 'sessions', 'mfa', 'passwords', 'clients']);
});

test('a support admin cannot create, edit or delete a realm', function () {
    $realm = Realm::factory()->create();

    $this->actingAs(globalAdmin(GlobalRoleSeeder::SUPPORT));
    $this->get('/admin/realms/create')->assertForbidden();
    $this->submitDeniedForm(CreateRealmForm::class, ['name' => 'Forbidden', 'slug' => 'forbidden'])->assertForbidden();
    $this->submitDeniedForm(UpdateRealmForm::class, ['name' => 'Renamed'], ['realm' => $realm->slug])->assertForbidden();
    $this->submitDeniedForm(RealmSettingForm::class, ...realmSetting($realm, 'access_token_lifetime', 300))->assertForbidden();
    $this->submitDeniedForm(DeleteRealmForm::class, ['name' => $realm->name], ['realm' => $realm->slug])->assertForbidden();

    expect(Realm::query()->where('slug', 'forbidden')->exists())->toBeFalse()
        ->and($realm->refresh()->name)->not->toBe('Renamed')
        ->and($realm->tokens()->accessTokenLifetime)->toBe(900);
});
