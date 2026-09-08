<?php
declare(strict_types=1);

use App\Auth\Models\User;
use Lattice\Notifications\Notification;

it('shows persisted notifications in the topbar bell', function () {
    $user = User::factory()->create();
    $tenant = $user->currentTenant()->firstOrFail();

    Notification::make()
        ->title('New tenant invitation')
        ->body('Jane invited you to join Acme.')
        ->sendToDatabase($user);

    $this->actingAs($user);

    $page = visit('/'.$tenant->slug.'/dashboard');

    $page->assertPresent('[data-test=notifications-trigger]')
        ->assertSeeIn('[data-test=notifications-badge]', '1')
        ->click('[data-test=notifications-trigger]')
        ->assertSeeIn('[data-test=notifications-panel]', 'New tenant invitation')
        ->assertNoJavaScriptErrors();
});
