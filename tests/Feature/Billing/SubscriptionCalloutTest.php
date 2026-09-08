<?php

declare(strict_types=1);

use App\Tenants\Enums\RolePreset;

use function Tests\Helpers\assumeNotNull;
use function Tests\Helpers\billingTabCallout;
use function Tests\Helpers\billingTabResponse;
use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

test('a past due tenant gets a non-dismissible danger callout keyed to billing state', function () {
    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'past_due');

    $callout = billingTabCallout($owner, $tenant);
    assumeNotNull($callout);

    expect($callout['variant'])->toBe('danger')
        ->and($callout['unique'])->toBe('billing.state')
        ->and($callout['dismissible'])->toBeFalse()
        ->and($callout['action'])->not->toBeNull();
});

test('a member without manage-billing gets the restricted message and no action', function () {
    [$member, $tenant] = subscribedOwner(RolePreset::Member, 'past_due');

    $callout = billingTabCallout($member, $tenant);
    assumeNotNull($callout);

    expect($callout['action'])->toBeNull()
        ->and($callout['message']['key'])->toBe('billing:state.past-due.message.restricted');
});

test('a canceling subscription gets a warning callout', function () {
    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'active', now()->addWeek());

    $callout = billingTabCallout($owner, $tenant);
    assumeNotNull($callout);

    expect($callout['variant'])->toBe('warning')
        ->and($callout['unique'])->toBe('billing.state');
});

test('a healthy subscription gets no callout', function () {
    [$owner, $tenant] = subscribedOwner();

    expect(billingTabCallout($owner, $tenant))->toBeNull();
});

test('a healthy tenant flashes a callout retraction rather than nothing, so a stale banner is cleared', function () {
    [$owner, $tenant] = subscribedOwner();

    $this->assertLatticeEffects(billingTabResponse($owner, $tenant))
        ->assertNotFlashed('callout')
        ->assertFlashed('retract-callout', fn (array $props) => expect($props['unique'])->toBe('billing.state'));
});

test('a stranger cannot leak another tenant\'s billing state through the callout flash', function () {
    [, $victimTenant] = subscribedOwner(RolePreset::Owner, 'past_due');
    $victimTenant->update(['name' => 'VictimCorp']);

    [$stranger, $ownTenant] = subscribedOwner(RolePreset::Owner, 'past_due', paddleId: 'sub_stranger');
    $ownTenant->update(['name' => 'StrangerCorp']);

    billingTabResponse($stranger, $victimTenant)->assertForbidden();

    $callout = billingTabCallout($stranger, $ownTenant);

    expect($callout)->not->toBeNull();

    $serialized = json_encode($callout, JSON_THROW_ON_ERROR);

    expect($serialized)->toContain('StrangerCorp')
        ->and($serialized)->not->toContain('VictimCorp');
});

test('a lattice component endpoint and the checkout endpoint leave no callout in the flash bag, while a page request still does', function () {
    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'past_due');

    $this->actingAs($owner)->getJson('/lattice/notifications')->assertOk();

    expect(session('inertia.flash_data'))->toBeNull();

    $this->actingAs($owner)->postJson(route('billing.checkout', ['current_tenant' => $tenant->slug]), [
        'price_id' => config('billing.plans.professional.monthly'),
    ])->assertStatus(409);

    expect(session('inertia.flash_data'))->toBeNull();

    $callout = billingTabCallout($owner, $tenant);
    assumeNotNull($callout);

    expect($callout['variant'])->toBe('danger');
});

test('a trial callout links to the billing page', function (Closure $trialEndsAt) {
    [$owner, $tenant] = tenantMember();
    $tenant->forceFill(['trial_ends_at' => $trialEndsAt()])->save();

    $callout = billingTabCallout($owner, $tenant);
    assumeNotNull($callout);

    expect($callout['action'])->not->toBeNull()
        ->and($callout['action']['type'])->toBe('link')
        ->and($callout['action']['props']['label'])->toBe(__('billing.state.view-plans'))
        ->and($callout['action']['props']['href'])->toBe(route('billing', ['current_tenant' => $tenant->slug]));
})->with([
    'trial ending' => [fn () => now()->addDays(2)],
    'expired' => [fn () => now()->subDay()],
]);

test('the canceling callout action button is labeled in the reader\'s locale', function () {
    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'active', now()->addWeek());
    $owner->update(['locale' => 'de']);

    $callout = billingTabCallout($owner->refresh(), $tenant);
    assumeNotNull($callout);

    expect($callout['action']['props']['label'])->toBe(__('billing.manage.resume', [], 'de'));
});
