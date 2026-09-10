<?php

declare(strict_types=1);

use App\Admin\Database\Seeders\GlobalRoleSeeder;
use App\Auth\Models\User;
use App\Auth\Ui\Layouts\AccountLayout;
use App\Realms\Models\Realm;
use Lattice\Support\Testing\ComponentNode;
use Lattice\Ui\PageSchema;

use function Tests\Helpers\grantGlobalRole;
use function Tests\Helpers\menuItemHrefs;

function accountLayoutSchema(User $user): ComponentNode
{
    auth()->setUser($user);

    $request = request();
    $request->setUserResolver(fn (): User => $user);

    $renderable = (new AccountLayout)->schema(PageSchema::make(), $request)->renderable();
    $schema = json_decode(json_encode($renderable, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

    return ComponentNode::root($schema);
}

/**
 * @return array<int, mixed>
 */
function accountUserMenuHrefs(User $user): array
{
    return menuItemHrefs(accountLayoutSchema($user)->firstOfTypeOrFail('dropdown', 'user-menu'));
}

it('offers the realm end-session as logout and the console only to admins', function () {
    (new GlobalRoleSeeder)->run();

    $user = User::factory()->for(Realm::master())->create();
    $hrefsBefore = accountUserMenuHrefs($user);

    grantGlobalRole($user, GlobalRoleSeeder::SUPPORT);
    $hrefsAfter = accountUserMenuHrefs($user->refresh());

    expect($hrefsBefore)->toBe(['/realms/admin/oauth/logout'])
        ->and($hrefsAfter)->toBe(['/admin/realms', '/realms/admin/oauth/logout']);
});

it('names the realm and links the brand to the realm account', function () {
    $realm = Realm::factory()->create(['slug' => 'acme', 'name' => 'Acme Corp']);
    $user = User::factory()->for($realm)->create();

    $schema = accountLayoutSchema($user);

    expect($schema->firstOfTypeOrFail('text', 'account-realm-name')->prop('text'))->toBe('Acme Corp')
        ->and($schema->firstOfTypeOrFail('link', 'account-brand')->prop('href'))->toBe('/realms/acme/account');
});
