<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Authentication\Http\Middleware\AuthenticateIdentity;
use Bambamboole\LaravelOidc\Server\Realms\Http\Middleware\ResolveRealm;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Lattice\Actions\Http\Controllers\ActionController;
use Lattice\Core\Http\Controllers\RefRefreshController;
use Lattice\Form\Http\Controllers\FormController;
use Lattice\Http\Controllers\BulkActionController;
use Lattice\Http\Controllers\FragmentController;
use Lattice\Http\Controllers\RemoteSourceTokenController;
use Lattice\Table\Http\Controllers\TableController;

// Lattice pages self-register their routes via #[AsPage]; only non-page routes live here.

Route::get('/', function (Request $request) {
    $user = $request->user();

    abort_unless($user instanceof User, 401);

    return $user->can(AdminPermission::Access)
        ? to_route('admin.realms')
        : to_route('account', ['realm' => $user->realm->slug]);
})
    ->middleware(['auth', 'verified'])
    ->name('home');

/*
 * The realm session cookie is scoped to /realms/{realm}, so a page rendered
 * there cannot reach the root Lattice endpoints. These are the same endpoints
 * once more below the realm, authenticated by the identity guard the way the
 * package's own account routes are; App\Realms\Support\RealmScopedLatticeEndpoints
 * points every Lattice URL minted during a realm request at them.
 */
Route::middleware([ResolveRealm::class])
    ->prefix('realms/{realm}/lattice')
    ->where(['realm' => '[A-Za-z0-9._-]+'])
    ->name('realm.lattice.')
    ->group(function (): void {
        Route::middleware('web')->post('refs/refresh', RefRefreshController::class)->name('refs.refresh');

        Route::middleware(['web', AuthenticateIdentity::class.':identity'])->group(function (): void {
            Route::match(['post', 'put', 'patch', 'delete'], 'forms/{form}', FormController::class)->where('form', '.*')->name('forms.handle');
            Route::match(['post', 'put', 'patch', 'delete'], 'actions/{action}', ActionController::class)->where('action', '.*')->name('actions.handle');
            Route::match(['post', 'put', 'patch', 'delete'], 'bulk-actions/{bulkAction}', BulkActionController::class)->where('bulkAction', '.*')->name('bulk-actions.handle');
            Route::get('tables/{table}', TableController::class)->where('table', '.*')->name('tables.show');
            Route::get('fragments/{fragment}', FragmentController::class)->where('fragment', '.*')->name('fragments.show');
            Route::post('remote-sources/{source}/token', RemoteSourceTokenController::class)->where('source', '.*')->name('remote-sources.token');
        });
    });
