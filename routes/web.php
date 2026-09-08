<?php
declare(strict_types=1);

use App\Tenants\Http\Controllers\AcceptInvitationController;
use App\Tenants\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Lattice pages self-register their routes via #[AsPage]; only non-page routes live here.

Route::get('/', fn () => to_route('dashboard.home'))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', AcceptInvitationController::class)->name('invitations.accept');
});

Route::middleware(['auth', 'verified'])->get('dashboard', function (Request $request) {
    $user = $request->user();
    $tenant = $user->currentTenant ?? $user->personalTenant();

    if (! $tenant instanceof Tenant) {
        return to_route('tenants.onboarding');
    }

    return to_route('dashboard', [...$request->query(), 'current_tenant' => $tenant->slug]);
})->name('dashboard.home');
