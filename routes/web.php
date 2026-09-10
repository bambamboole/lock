<?php
declare(strict_types=1);

use App\Auth\Models\User;
use App\Realms\Http\Controllers\AcceptInvitationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Lattice pages self-register their routes via #[AsPage]; only non-page routes live here.

Route::get('/', fn () => to_route('dashboard.home'))->name('home');

Route::get('/dashboard', function (Request $request) {
    $user = $request->user();
    abort_unless($user instanceof User, 401);

    return to_route('dashboard', ['current_realm' => $user->realm->slug]);
})->middleware(['auth', 'verified'])->name('dashboard.home');

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', AcceptInvitationController::class)->name('invitations.accept');
});
