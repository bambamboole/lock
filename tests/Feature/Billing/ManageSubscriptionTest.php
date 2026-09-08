<?php

declare(strict_types=1);

use App\Billing\Ui\Actions\CancelSubscription;
use App\Billing\Ui\Actions\ResumeSubscription;
use App\Tenants\Enums\RolePreset;
use Illuminate\Support\Facades\Http;

use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

test('an owner can cancel an active subscription', function () {
    Http::fake(['*subscriptions/*/cancel' => Http::response(['data' => [
        'status' => 'canceled',
        'scheduled_change' => ['effective_at' => now()->addMonth()->toIso8601String()],
    ]])]);

    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(CancelSubscription::class, [], ['tenant' => $tenant->slug])
        ->assertOk();

    Http::assertSent(fn ($request) => str_contains($request->url(), 'subscriptions/sub_manage/cancel'));
    expect($tenant->subscriptions()->firstOrFail()->ends_at)->not->toBeNull();
});

test('a member without manage-billing permission cannot cancel', function () {
    [$member, $tenant] = subscribedOwner(RolePreset::Member);

    $this->actingAs($member)
        ->callDeniedAction(CancelSubscription::class, [], ['tenant' => $tenant->slug])
        ->assertForbidden();
});

test('canceling without an active subscription fails instead of pretending success', function () {
    Http::fake();

    [$owner, $tenant] = tenantMember();

    $this->actingAs($owner)
        ->callAction(CancelSubscription::class, [], ['tenant' => $tenant->slug])
        ->assertStatus(422)
        ->assertJsonPath('effects.0.type', 'toast')
        ->assertJsonPath('effects.0.props.message', __('billing.manage.no-subscription'))
        ->assertJsonPath('effects.0.props.variant', 'danger');

    Http::assertNothingSent();
});

test('canceling a subscription that is already canceled fails', function () {
    Http::fake();

    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'canceled', now()->subDay());

    $this->actingAs($owner)
        ->callAction(CancelSubscription::class, [], ['tenant' => $tenant->slug])
        ->assertStatus(422)
        ->assertJsonPath('effects.0.props.message', __('billing.manage.no-subscription'));

    Http::assertNothingSent();
});

test('an owner can resume a subscription that is canceled but in its grace period', function () {
    Http::fake(['*subscriptions/*' => Http::response(['data' => ['status' => 'active']])]);

    [$owner, $tenant] = subscribedOwner(RolePreset::Owner, 'canceled', now()->addWeek());

    $this->actingAs($owner)
        ->callAction(ResumeSubscription::class, [], ['tenant' => $tenant->slug])
        ->assertOk();

    expect($tenant->subscriptions()->firstOrFail()->ends_at)->toBeNull();
});

test('resuming an active subscription fails instead of pretending success', function () {
    Http::fake();

    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(ResumeSubscription::class, [], ['tenant' => $tenant->slug])
        ->assertStatus(422)
        ->assertJsonPath('effects.0.props.message', __('billing.manage.nothing-to-resume'));

    Http::assertNothingSent();
});
