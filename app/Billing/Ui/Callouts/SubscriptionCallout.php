<?php

declare(strict_types=1);

namespace App\Billing\Ui\Callouts;

use App\Auth\Models\User;
use App\Billing\Enums\SubscriptionState;
use App\Billing\Ui\Actions\ResumeSubscription;
use App\Billing\Ui\Actions\UpdatePaymentMethod;
use App\Tenants\Enums\TenantPermission;
use App\Tenants\Models\Tenant;
use DateTimeInterface;
use Lattice\Actions\Components\Action;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\Link;
use Lattice\Ui\Effects\Builtin\Callout;
use Lattice\Ui\Enums\Variant;
use Lattice\Ui\I18n\Values\Translatable;

final class SubscriptionCallout
{
    public const string KEY = 'billing.state';

    public static function for(Tenant $tenant, User $user): ?Callout
    {
        return match (SubscriptionState::for($tenant)) {
            SubscriptionState::PastDue => self::pastDue($tenant, $user),
            SubscriptionState::Canceling => self::canceling($tenant, $user),
            SubscriptionState::TrialEnding => self::trialEnding($tenant, $user),
            SubscriptionState::Expired => self::expired($tenant, $user),
            default => null,
        };
    }

    private static function pastDue(Tenant $tenant, User $user): Callout
    {
        $canManage = $user->can(TenantPermission::ManageBilling, $tenant);

        return self::build(
            Variant::Danger,
            rt('billing:state.past-due.title'),
            self::message('past-due', $canManage, ['tenant' => $tenant->name]),
            $canManage ? Action::use(UpdatePaymentMethod::class, ['tenant' => $tenant->slug]) : null,
        )->dismissible(false);
    }

    private static function canceling(Tenant $tenant, User $user): Callout
    {
        $canManage = $user->can(TenantPermission::ManageBilling, $tenant);

        return self::build(
            Variant::Warning,
            rt('billing:state.canceling.title'),
            self::message('canceling', $canManage, ['date' => $tenant->subscription()?->ends_at]),
            $canManage ? Action::use(ResumeSubscription::class, ['tenant' => $tenant->slug]) : null,
        );
    }

    private static function trialEnding(Tenant $tenant, User $user): Callout
    {
        $canManage = $user->can(TenantPermission::ManageBilling, $tenant);

        return self::build(
            Variant::Info,
            rt('billing:state.trial-ending.title'),
            self::message('trial-ending', $canManage, ['date' => $tenant->trial_ends_at]),
            self::viewPlansLink($tenant),
        );
    }

    private static function expired(Tenant $tenant, User $user): Callout
    {
        $canManage = $user->can(TenantPermission::ManageBilling, $tenant);

        return self::build(
            Variant::Danger,
            rt('billing:state.expired.title'),
            self::message('expired', $canManage),
            self::viewPlansLink($tenant),
        );
    }

    private static function viewPlansLink(Tenant $tenant): Link
    {
        return Link::make(__('billing.state.view-plans'))
            ->href(route('billing', ['current_tenant' => $tenant->slug]));
    }

    /**
     * @param  array<string, string|int|float|bool|DateTimeInterface|null>  $replacements
     */
    private static function message(string $state, bool $canManage, array $replacements = []): Translatable
    {
        $key = $canManage
            ? "billing:state.{$state}.message.default"
            : "billing:state.{$state}.message.restricted";

        return rt($key)->with(array_filter(
            $replacements,
            static fn (string|int|float|bool|DateTimeInterface|null $value): bool => $value !== null,
        ));
    }

    private static function build(
        Variant $variant,
        Translatable $title,
        Translatable $message,
        ?Component $action,
    ): Callout {
        $callout = Callout::make($message, $variant)->title($title)->unique(self::KEY);

        return $action instanceof Component ? $callout->action($action) : $callout;
    }
}
