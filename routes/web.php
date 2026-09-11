<?php
declare(strict_types=1);

use App\Admin\Enums\AdminPermission;
use App\Realms\Http\Controllers\DomainCheckController;
use App\Shared\Auth\Support\RequestUser;
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

Route::get('/', function (Request $request) {
    $user = RequestUser::of($request);

    abort_if($user === null, 401);

    return $user->can(AdminPermission::Access)
        ? to_route('admin.realms')
        : to_route('account');
})
    ->middleware(['auth', 'verified'])
    ->name('home');

/*
 * The account page authenticates with the identity guard, the root Lattice
 * endpoints with the console's web guard — and on the master realm's host both
 * share one session. These are the same endpoints once more for the account
 * area, behind the identity guard the way the package's own account routes
 * are; App\Realms\Support\RealmScopedLatticeEndpoints points every Lattice
 * URL minted during a realm request at them.
 */
Route::middleware([ResolveRealm::class])
    ->prefix('account/lattice')
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

Route::get('.well-known/lock/domain-check', DomainCheckController::class)->name('realm.domain-check');
