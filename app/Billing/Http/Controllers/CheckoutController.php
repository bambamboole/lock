<?php

declare(strict_types=1);

namespace App\Billing\Http\Controllers;

use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class CheckoutController
{
    public function __invoke(Request $request, Tenant $current_tenant): JsonResponse
    {
        abort_unless($request->user()?->can(TenantPermission::ManageBilling, $current_tenant) ?? false, 403);

        if ($current_tenant->subscribed()) {
            return response()->json([
                'message' => __('billing.checkout.already-subscribed'),
                'errors' => ['price_id' => [__('billing.checkout.already-subscribed')]],
            ], 409);
        }

        $priceId = (string) $request->input('price_id');

        if (! in_array($priceId, $this->validPriceIds(), true)) {
            return response()->json([
                'message' => __('billing.checkout.invalid-price'),
                'errors' => ['price_id' => [__('billing.checkout.invalid-price')]],
            ], 422);
        }

        $options = $current_tenant
            ->subscribe($priceId, 'default')
            ->returnTo(route('billing', ['current_tenant' => $current_tenant->slug]))
            ->options();

        return response()->json(['options' => $options]);
    }

    /**
     * @return array<int, string>
     */
    private function validPriceIds(): array
    {
        $ids = [];

        foreach (config('billing.plans') as $plan) {
            foreach (['monthly', 'yearly'] as $cycle) {
                if (! empty($plan[$cycle])) {
                    $ids[] = $plan[$cycle];
                }
            }
        }

        return $ids;
    }
}
