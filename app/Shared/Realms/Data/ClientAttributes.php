<?php
declare(strict_types=1);

namespace App\Shared\Realms\Data;

use Bambamboole\LaravelOidc\Server\Clients\Enums\TokenEndpointAuthMethod;

final readonly class ClientAttributes
{
    public const array GRANT_TYPES = ['authorization_code', 'refresh_token', 'client_credentials'];

    /** @var list<string> */
    public array $grantTypes;

    /**
     * @param  list<string>  $redirectUris
     * @param  list<string>  $postLogoutRedirectUris
     * @param  list<string>  $grantTypes
     */
    public function __construct(
        public string $name,
        public TokenEndpointAuthMethod $tokenEndpointAuthMethod,
        public array $redirectUris,
        public array $postLogoutRedirectUris,
        array $grantTypes,
        public bool $consentRequired = true,
        public ?string $backchannelLogoutUri = null,
    ) {
        $this->grantTypes = array_values(array_intersect(self::GRANT_TYPES, $grantTypes));
    }

    public function confidential(): bool
    {
        return $this->tokenEndpointAuthMethod->requiresSecret();
    }

    public function usesAuthorizationCode(): bool
    {
        return in_array('authorization_code', $this->grantTypes, true);
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'token_endpoint_auth_method' => $this->tokenEndpointAuthMethod,
            'redirect_uris' => $this->redirectUris,
            'post_logout_redirect_uris' => $this->postLogoutRedirectUris,
            'grant_types' => $this->grantTypes,
            'consent_required' => $this->consentRequired,
            'backchannel_logout_uri' => $this->backchannelLogoutUri,
        ];
    }
}
