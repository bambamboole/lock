<?php

declare(strict_types=1);

namespace App\Billing\Services;

use App\Billing\Data\PlanPricing;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Cashier;

final class PricingService
{
    private const int CACHE_TTL_SECONDS = 300;

    /**
     * @return array<string, PlanPricing>
     */
    public function plans(string $billingCycle = 'monthly'): array
    {
        $paddlePrices = $this->fetchPaddlePrices($billingCycle);

        $plans = [];

        foreach (array_keys($this->planConfigs()) as $key) {
            $plans[$key] = $this->buildPlanPricing($key, $billingCycle, $paddlePrices);
        }

        return $plans;
    }

    public function plan(string $key, string $billingCycle = 'monthly'): PlanPricing
    {
        return $this->plans($billingCycle)[$key];
    }

    /**
     * @return array<string, array{formatted: string, raw: int, currency: string}>
     */
    private function fetchPaddlePrices(string $billingCycle): array
    {
        $priceIds = $this->resolvePaddlePriceIds($billingCycle);

        if ($priceIds === []) {
            return [];
        }

        try {
            return Cache::remember("paddle-prices-{$billingCycle}", self::CACHE_TTL_SECONDS, function () use ($priceIds): array {
                $previews = Cashier::previewPrices(array_values($priceIds));

                $result = [];

                foreach ($priceIds as $plan => $priceId) {
                    $preview = $previews->first(fn ($p): bool => $p->price['id'] === $priceId);

                    if ($preview) {
                        $result[$plan] = [
                            'formatted' => $preview->total(),
                            'raw' => (int) $preview->rawTotal(),
                            'currency' => $preview->currency()->getCode(),
                        ];
                    }
                }

                return $result;
            });
        } catch (\Exception $e) {
            Log::warning('Failed to fetch Paddle prices', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * @return array<string, string>
     */
    private function resolvePaddlePriceIds(string $billingCycle): array
    {
        $priceIds = [];

        foreach ($this->planConfigs() as $plan => $planConfig) {
            $priceId = $planConfig[$billingCycle] ?? null;

            if (is_string($priceId) && $priceId !== '') {
                $priceIds[$plan] = $priceId;
            }
        }

        return $priceIds;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function planConfigs(): array
    {
        $plans = config('billing.plans');

        return is_array($plans) ? $plans : [];
    }

    /**
     * @param  array<string, array{formatted: string, raw: int, currency: string}>  $paddlePrices
     */
    private function buildPlanPricing(string $key, string $billingCycle, array $paddlePrices): PlanPricing
    {
        $planConfig = config("billing.plans.{$key}");
        $currency = config('billing.currency', 'EUR');
        $paddlePriceId = $planConfig[$billingCycle] ?? null;
        $featureKeys = $planConfig['listed-features'] ?? [];

        if (isset($paddlePrices[$key])) {
            return new PlanPricing(
                key: $key,
                name: __("billing.plans.{$key}.name"),
                description: __("billing.plans.{$key}.description"),
                formattedPrice: $paddlePrices[$key]['formatted'],
                rawPrice: $paddlePrices[$key]['raw'],
                currency: $paddlePrices[$key]['currency'],
                featureKeys: $featureKeys,
                paddlePriceId: $paddlePriceId,
            );
        }

        $fallbackPrice = (int) ($planConfig['price'] ?? 0);

        return new PlanPricing(
            key: $key,
            name: __("billing.plans.{$key}.name"),
            description: __("billing.plans.{$key}.description"),
            formattedPrice: number_format($fallbackPrice, 0).' '.$currency,
            rawPrice: $fallbackPrice * 100,
            currency: $currency,
            featureKeys: $featureKeys,
            paddlePriceId: $paddlePriceId,
        );
    }
}
