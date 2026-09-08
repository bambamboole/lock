<?php

declare(strict_types=1);

use App\Billing\Ui\Actions\UpdatePaymentMethod;
use App\Tenants\Enums\RolePreset;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Exceptions\PaddleException;

use function Tests\Helpers\subscribedOwner;
use function Tests\Helpers\tenantMember;

test('an owner is redirected to the paddle payment method url', function () {
    Http::fake(['*subscriptions/sub_manage' => Http::response(['data' => [
        'management_urls' => ['update_payment_method' => 'https://paddle.test/update'],
    ]])]);

    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(UpdatePaymentMethod::class, [], ['tenant' => $tenant->slug])
        ->assertOk()
        ->assertJsonPath('effects.0.type', 'redirect')
        ->assertJsonPath('effects.0.props.url', 'https://paddle.test/update');
});

test('a member without manage-billing permission cannot update the payment method', function () {
    [$member, $tenant] = subscribedOwner(RolePreset::Member);

    $this->actingAs($member)
        ->callDeniedAction(UpdatePaymentMethod::class, [], ['tenant' => $tenant->slug])
        ->assertForbidden();
});

test('an owner without a subscription gets a failure instead of a redirect', function () {
    [$owner, $tenant] = tenantMember();

    $this->actingAs($owner)
        ->callAction(UpdatePaymentMethod::class, [], ['tenant' => $tenant->slug])
        ->assertStatus(422)
        ->assertJsonPath('effects.0.type', 'toast')
        ->assertJsonPath('effects.0.props.message', __('billing.manage.no-subscription'))
        ->assertJsonPath('effects.0.props.variant', 'danger');
});

test('a paddle rejection returns a friendly failure without leaking the paddle error', function () {
    Exceptions::fake();
    Http::fake(['*subscriptions/sub_manage' => Http::response([
        'error' => ['detail' => 'subscription is not in a state that allows this'],
    ], 400)]);

    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(UpdatePaymentMethod::class, [], ['tenant' => $tenant->slug])
        ->assertStatus(422)
        ->assertJsonPath('effects.0.type', 'toast')
        ->assertJsonPath('effects.0.props.message', __('billing.manage.provider-error'))
        ->assertJsonPath('effects.0.props.variant', 'danger')
        ->assertDontSee('subscription is not in a state that allows this');

    Exceptions::assertReported(PaddleException::class);
});

test('a paddle connection failure returns a friendly failure without leaking the exception', function () {
    Exceptions::fake();
    Http::fake(fn () => throw new ConnectionException('Could not connect to Paddle'));

    [$owner, $tenant] = subscribedOwner();

    $this->actingAs($owner)
        ->callAction(UpdatePaymentMethod::class, [], ['tenant' => $tenant->slug])
        ->assertStatus(422)
        ->assertJsonPath('effects.0.type', 'toast')
        ->assertJsonPath('effects.0.props.message', __('billing.manage.provider-error'))
        ->assertJsonPath('effects.0.props.variant', 'danger')
        ->assertDontSee('Could not connect to Paddle');

    Exceptions::assertReported(ConnectionException::class);
});
