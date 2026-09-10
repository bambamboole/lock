<?php
declare(strict_types=1);

use App\Auth\Models\User;

use function Tests\Helpers\visitAccountAs;

/**
 * Step two of the wizard is prepared by Lattice's resolve sub-request, so it is
 * only ever right in a browser: the server has the payload either way, but it
 * reaches the field through the resolved node rather than the schema the page
 * shipped with.
 */
beforeEach(function () {
    $this->user = User::factory()->create();
    session(['auth.password_confirmed_at' => time()]);
});

it('prepares the preselected method without the user picking one', function () {
    visitAccountAs($this->user)
        ->click('button:has-text("Add method")')
        ->assertSee('Passkey')
        ->click('[data-test="wizard-next"]')
        ->assertSee('Only for you, so you can tell this device apart later.')
        ->assertNoJavaScriptErrors();
});

it('prepares the authenticator app once it is picked', function () {
    visitAccountAs($this->user)
        ->click('button:has-text("Add method")')
        ->click('text=Authenticator app')
        ->click('[data-test="wizard-next"]')
        ->assertSee('Or enter this setup key in your authenticator app:')
        ->assertNoJavaScriptErrors();
});
