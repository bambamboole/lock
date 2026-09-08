<?php
declare(strict_types=1);

namespace App\Tenants\Http\Middleware;

use App\Auth\Models\OAuthAccessToken;
use App\Auth\Models\User;
use App\Tenants\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\AccessToken;
use Symfony\Component\HttpFoundation\Response;

final class ResolveApiTenant
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user instanceof User, 401);

        $scopeToken = $user->token();
        $dbToken = $scopeToken instanceof AccessToken
            ? OAuthAccessToken::find($scopeToken->oauth_access_token_id)
            : null;
        $tenantId = $dbToken instanceof OAuthAccessToken ? $dbToken->tenant_id : null;

        abort_if($tenantId === null, 403, __('This token is not bound to a tenant.'));

        $tenant = Tenant::find($tenantId);

        abort_unless($tenant instanceof Tenant && $user->belongsToTenant($tenant), 403, __('Tenant access denied.'));

        $user->setRelation('currentTenant', $tenant);

        $tenant->makeCurrent();

        return $next($request);
    }
}
