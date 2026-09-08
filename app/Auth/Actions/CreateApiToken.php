<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Enums\AuthAuditEvent;
use App\Auth\Models\OAuthAccessToken;
use App\Auth\Models\User;
use App\Auth\Support\GrantableTenantScopes;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final readonly class CreateApiToken
{
    public function __construct(private GrantableTenantScopes $grantableScopes) {}

    /**
     * @param  array<int, string>  $requestedScopes
     */
    public function handle(User $user, Tenant $tenant, string $name, array $requestedScopes): string
    {
        abort_unless($user->belongsToTenant($tenant), 403, __('tenant.settings.api-tokens.not-a-member'));

        $grantable = $this->grantableScopes->for($user, $tenant);

        $ungrantable = array_values(array_diff($requestedScopes, $grantable));

        if ($ungrantable !== []) {
            throw ValidationException::withMessages([
                'scopes' => __('tenant.settings.api-tokens.ungrantable-scopes', [
                    'scopes' => implode(', ', $ungrantable),
                ]),
            ]);
        }

        $result = $user->createToken($name, $requestedScopes);

        $token = $result->getToken();

        if (! $token instanceof OAuthAccessToken) {
            throw new RuntimeException(sprintf(
                'Passport issued %s; the token model must be %s for tokens to carry a tenant.',
                $token === null ? 'no token' : 'a '.$token::class,
                OAuthAccessToken::class,
            ));
        }

        $token->forceFill(['tenant_id' => $tenant->id])->save();

        Audit::record(AuthAuditEvent::ApiTokenCreated, $token, $tenant, [
            'name' => $name,
            'scopes' => $requestedScopes,
        ]);

        return $result->accessToken;
    }
}
