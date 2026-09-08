<?php

declare(strict_types=1);

namespace App\Billing\Data;

final readonly class PlanPricing
{
    /**
     * @param  array<int, string>  $featureKeys
     */
    public function __construct(
        public string $key,
        public string $name,
        public string $description,
        public string $formattedPrice,
        public int $rawPrice,
        public string $currency,
        public array $featureKeys,
        public ?string $paddlePriceId = null,
    ) {}

    /**
     * @return array<int, string>
     */
    public function features(): array
    {
        return array_map(static fn (string $key): string => __($key), $this->featureKeys);
    }

    public function isPaid(): bool
    {
        return $this->rawPrice > 0;
    }
}
