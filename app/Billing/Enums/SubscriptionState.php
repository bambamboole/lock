<?php

declare(strict_types=1);

namespace App\Billing\Enums;

use App\Tenants\Models\Tenant;

enum SubscriptionState
{
    case Trialing;
    case TrialEnding;
    case Active;
    case PastDue;
    case Canceling;
    case Expired;

    public static function for(Tenant $tenant): self
    {
        $subscription = $tenant->subscription();

        if ($subscription !== null) {
            if ($subscription->pastDue()) {
                return self::PastDue;
            }

            if ($subscription->active() && $subscription->ends_at?->isFuture()) {
                return self::Canceling;
            }

            if ($subscription->valid()) {
                return self::Active;
            }
        }

        $trialEndsAt = $tenant->trial_ends_at;

        if ($trialEndsAt !== null && $trialEndsAt->isFuture()) {
            $warningDays = (int) config('billing.trial-warning-days', 3);

            return $trialEndsAt->isBefore(now()->addDays($warningDays))
                ? self::TrialEnding
                : self::Trialing;
        }

        return self::Expired;
    }
}
