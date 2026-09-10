<?php
declare(strict_types=1);

use App\Auth\Models\User;

use function Tests\Helpers\visitAccountAs;

/**
 * A package ships its translations under its own i18next namespace, and i18next
 * only fetches the namespaces named at boot. Miss one and every string that
 * package's React components render falls back to the English default baked into
 * the component — on a page whose server-rendered half is translated, so nothing
 * looks broken enough to notice.
 */
it('renders package strings in the user locale', function () {
    session(['auth.password_confirmed_at' => time()]);

    visitAccountAs(User::factory()->create(['locale' => 'de']))
        ->click('button:has-text("Methode hinzufügen")')
        ->click('[data-test="wizard-next"]')
        // Rendered by the oidc-ui package's own React field, not by the server.
        ->assertSee('Nur für dich, damit du dieses Gerät später wiedererkennst.')
        ->assertNoJavaScriptErrors();
});
