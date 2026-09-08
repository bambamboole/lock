<?php
declare(strict_types=1);

use App\Billing\Http\Controllers\CheckoutController;
use App\Tenants\Http\Controllers\AcceptInvitationController;
use App\Tenants\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Lattice pages self-register their routes via #[AsPage]; only non-page routes live here.

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', AcceptInvitationController::class)->name('invitations.accept');
});

Route::middleware(['auth', 'verified'])
    ->post('{current_tenant}/billing/checkout', CheckoutController::class)
    ->name('billing.checkout');

// Billing lives in the tenant settings; the old page URL keeps its route name so
// every link, callout and guard that points at `billing` lands on that tab.
Route::middleware(['auth', 'verified', 'can:view,current_tenant'])
    ->get('{current_tenant}/billing', fn (Tenant $current_tenant) => to_route('tenant.settings.edit', [
        'current_tenant' => $current_tenant->slug,
        'tabs' => 'billing',
    ]))
    ->name('billing');

Route::middleware(['auth', 'verified'])->get('dashboard', function (Request $request) {
    $user = $request->user();
    $tenant = $user->currentTenant ?? $user->personalTenant();

    if (! $tenant instanceof Tenant) {
        return to_route('tenants.onboarding');
    }

    return to_route('dashboard', [...$request->query(), 'current_tenant' => $tenant->slug]);
})->name('dashboard.home');
