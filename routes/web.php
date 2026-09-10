<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Lattice pages self-register their routes via #[AsPage]; only non-page routes live here.

Route::get('/', fn (Request $request) => $request->user()?->can(AdminPermission::Access)
    ? to_route('admin.realms')
    : to_route('profile.edit'))
    ->middleware(['auth', 'verified'])
    ->name('home');
