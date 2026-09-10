<?php

declare(strict_types=1);

use App\Auth\Models\User;
use App\Auth\Ui\Forms\NameForm;
use App\Realms\Models\Realm;
use Illuminate\Auth\SessionGuard;
use Inertia\Testing\AssertableInertia;
use Lattice\Support\Testing\ComponentNode;

use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\latticeSchema;

/**
 * The account page is the one place a realm user lands. It lives below the
 * realm because that is where the realm's session cookie is sent, so these
 * tests cover the composition that makes that work: the identity guard, the
 * realm-scoped Lattice endpoints, and the isolation between realms.
 *
 * @return array<string, string>
 */
function identitySessionFor(User $user): array
{
    return ['login_identity_'.sha1(SessionGuard::class) => $user->getKey()];
}

it('renders under the account layout for a realm user', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);
    $user = User::factory()->for($realm)->create();

    $response = $this->actingAs($user, 'identity')
        ->get(route('account', ['realm' => 'acme']))
        ->assertOk();

    AssertableInertia::fromTestResponse($response)
        ->where('lattice.layout.key', 'account');
});

it('sends a guest to the realm sign-in', function () {
    Realm::factory()->create(['slug' => 'acme']);

    $this->get(route('account', ['realm' => 'acme']))
        ->assertRedirect(route('identity.login', ['realm' => 'acme']));
});

it('does not let a session from one realm open another realm account', function () {
    $acme = Realm::factory()->create(['slug' => 'acme']);
    Realm::factory()->create(['slug' => 'globex']);
    $user = User::factory()->for($acme)->create();

    $this->withSession(identitySessionFor($user))
        ->get(route('account', ['realm' => 'globex']))
        ->assertRedirect(route('identity.login', ['realm' => 'globex']));

    auth()->forgetGuards();

    $this->withSession(identitySessionFor($user))
        ->get(route('account', ['realm' => 'acme']))
        ->assertOk();
});

it('mints its form endpoints below the realm and accepts the identity session there', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);
    $user = User::factory()->for($realm)->create(['name' => 'Old Name']);

    $page = $this->actingAs($user, 'identity')->get(route('account', ['realm' => 'acme']))->assertOk();

    $nameForm = latticeSchema($page)->find(fn (ComponentNode $node): bool => $node->type() === 'form'
        && $node->find(fn (ComponentNode $field): bool => $field->prop('name') === 'name') instanceof ComponentNode);
    assumeNotNull($nameForm, 'The account page renders the name form.');

    $action = $nameForm->prop('action');
    $sessionCookie = config('session.cookie').'-oidc-acme';
    $sessionId = $page->getCookie($sessionCookie)?->getValue();
    assumeNotNull($sessionId, 'The account page starts the realm session.');

    expect($action)->toBeString()->toStartWith('/realms/acme/lattice/forms/');

    $this->actingAs($user, 'identity')
        ->withCookie($sessionCookie, $sessionId)
        ->patch((string) $action, ['name' => 'New Name'], [
            'X-Inertia' => 'true',
            'X-Lattice-Ref' => (string) $nameForm->prop('ref'),
        ])
        ->assertRedirect(route('account', ['realm' => 'acme']));

    expect($user->refresh()->name)->toBe('New Name');
});

it('keeps root requests on the root Lattice endpoints', function () {
    $user = User::factory()->for(Realm::master())->create();

    $this->actingAs($user)
        ->submitForm(NameForm::class, ['name' => 'Root Name'])
        ->assertRedirect(route('account', ['realm' => Realm::master()->slug]));

    expect($user->refresh()->name)->toBe('Root Name');
});

it('sends a signed-in identity from the realm sign-in to its account', function () {
    $realm = Realm::factory()->create(['slug' => 'acme']);
    $user = User::factory()->for($realm)->create();

    $this->actingAs($user, 'identity')
        ->get(route('identity.login', ['realm' => 'acme']))
        ->assertRedirect(route('account', ['realm' => 'acme']));
});
