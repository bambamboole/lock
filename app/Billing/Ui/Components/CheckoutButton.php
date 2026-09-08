<?php

declare(strict_types=1);

namespace App\Billing\Ui\Components;

use Lattice\Core\Attributes\AsComponent;
use Lattice\Ui\Components\Component;

#[AsComponent('billing.checkout')]
final class CheckoutButton extends Component
{
    public string $label = '';

    public string $checkoutUrl = '';

    public string $priceId = '';

    public string $token = '';

    public bool $sandbox = false;

    public static function make(string $label, string $checkoutUrl, string $priceId, string $token, bool $sandbox, ?string $key = null): static
    {
        $button = new self($key);
        $button->label = $label;
        $button->checkoutUrl = $checkoutUrl;
        $button->priceId = $priceId;
        $button->token = $token;
        $button->sandbox = $sandbox;

        return $button;
    }
}
