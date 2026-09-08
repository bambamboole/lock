<?php
declare(strict_types=1);

use App\Realms\Http\Controllers\AcceptInvitationController;
use Illuminate\Support\Facades\Route;

// Lattice pages self-register their routes via #[AsPage]; only non-page routes live here.

Route::get('/', fn () => to_route('dashboard'))->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('invitations/{invitation}/accept', AcceptInvitationController::class)->name('invitations.accept');
});
