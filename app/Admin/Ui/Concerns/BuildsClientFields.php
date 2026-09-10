<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Shared\Realms\Data\ClientAttributes;
use Bambamboole\LaravelOidc\Server\Clients\Enums\TokenEndpointAuthMethod;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Closure;
use Illuminate\Validation\Rule;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\Components\Field;
use Lattice\Form\Components\Select;
use Lattice\Form\Components\Textarea;
use Lattice\Form\Components\TextInput;
use Lattice\Form\Components\Toggle;
use Lattice\Form\FormData;

/**
 * The client fields, keyed so a form can render all of them or exactly one.
 *
 * Type, grant types and redirect URIs constrain each other, so every one of
 * them carries the same whole-client rule: editing one alone is still checked
 * against what the client already stores, not against an empty payload.
 */
trait BuildsClientFields
{
    /** @var list<string> */
    private const array CREATION_FIELDS = [
        'name',
        'token_endpoint_auth_method',
        'grant_types',
        'redirect_uris',
        'post_logout_redirect_uris',
    ];

    /**
     * @return array<string, Field>
     */
    private function clientFields(?Client $client): array
    {
        return [
            'name' => TextInput::make('name', __('admin.clients.fields.name.label'))
                ->value($client?->name, editable: true)
                ->required()->rules(['string', 'max:255']),
            'token_endpoint_auth_method' => Select::make('token_endpoint_auth_method', __('admin.clients.fields.type.label'))
                ->value($client?->token_endpoint_auth_method->value ?? TokenEndpointAuthMethod::ClientSecretBasic->value, editable: true)
                ->options(array_map(
                    fn (TokenEndpointAuthMethod $method): mixed => Select::option(__('admin.clients.types.'.str_replace('_', '-', $method->value)), $method->value),
                    TokenEndpointAuthMethod::cases(),
                ))
                ->helperText(__('admin.clients.fields.type.help-text'))
                ->required()->rules([Rule::enum(TokenEndpointAuthMethod::class)])
                ->rules(fn (FormData $data): array => [$this->clientConsistencyRule($data, $client)]),
            'grant_types' => CheckboxGroup::make('grant_types', __('admin.clients.fields.grant-types.label'))
                ->value($client->grant_types ?? ['authorization_code', 'refresh_token'], editable: true)
                ->options(array_map(
                    fn (string $grantType): mixed => CheckboxGroup::option(__('admin.clients.grant-types.'.str_replace('_', '-', $grantType)), $grantType),
                    ClientAttributes::GRANT_TYPES,
                ))
                ->helperText(__('admin.clients.fields.grant-types.help-text'))
                ->required()->rules(['array', 'min:1', Rule::in(ClientAttributes::GRANT_TYPES)])
                ->rules(fn (FormData $data): array => [$this->clientConsistencyRule($data, $client)]),
            'redirect_uris' => Textarea::make('redirect_uris', __('admin.clients.fields.redirect-uris.label'))
                ->value(implode("\n", $client->redirect_uris ?? []), editable: true)->rows(3)
                ->helperText(__('admin.clients.fields.redirect-uris.help-text'))
                ->rules(['nullable', 'string', 'max:20000'])
                ->rules(fn (FormData $data): array => [
                    Rule::requiredIf(fn (): bool => in_array('authorization_code', $this->clientValues($data, $client)['grant_types'], true)),
                    $this->uriListRule(),
                    $this->clientConsistencyRule($data, $client),
                ])
                ->message('required', __('admin.clients.validation.redirect-uri-required')),
            'post_logout_redirect_uris' => Textarea::make('post_logout_redirect_uris', __('admin.clients.fields.post-logout-redirect-uris.label'))
                ->value(implode("\n", $client->post_logout_redirect_uris ?? []), editable: true)->rows(2)
                ->helperText(__('admin.clients.fields.post-logout-redirect-uris.help-text'))
                ->rules(['nullable', 'string', 'max:20000'])
                ->rules(fn (): array => [$this->uriListRule()]),
            'consent_required' => Toggle::make('consent_required', __('admin.clients.fields.consent-required.label'))
                ->value($client->consent_required ?? true, editable: true)
                ->helperText(__('admin.clients.fields.consent-required.help-text'))
                ->rules(['boolean']),
            'backchannel_logout_uri' => TextInput::make('backchannel_logout_uri', __('admin.clients.fields.backchannel-logout-uri.label'))
                ->value($client?->backchannel_logout_uri, editable: true)
                ->helperText(__('admin.clients.fields.backchannel-logout-uri.help-text'))
                ->rules(['nullable', 'string', 'max:2048', 'url:http,https']),
        ];
    }

    /**
     * @return array<int, Field>
     */
    private function clientCreationFields(): array
    {
        return array_values(array_intersect_key($this->clientFields(null), array_flip(self::CREATION_FIELDS)));
    }

    private function clientAttributes(FormData $data, ?Client $client): ClientAttributes
    {
        $values = $this->clientValues($data, $client);
        $backchannelLogoutUri = trim($values['backchannel_logout_uri']);

        return new ClientAttributes(
            name: $values['name'],
            tokenEndpointAuthMethod: TokenEndpointAuthMethod::from($values['token_endpoint_auth_method']),
            redirectUris: $this->lines($values['redirect_uris']),
            postLogoutRedirectUris: $this->lines($values['post_logout_redirect_uris']),
            grantTypes: $values['grant_types'],
            consentRequired: $values['consent_required'],
            backchannelLogoutUri: $backchannelLogoutUri === '' ? null : $backchannelLogoutUri,
        );
    }

    /**
     * The client as it would stand after the submitted fields are applied:
     * a key the payload does not carry keeps the stored value, and a key it
     * carries as null (a cleared input) clears it.
     *
     * @return array{name: string, token_endpoint_auth_method: string, grant_types: list<string>, redirect_uris: string, post_logout_redirect_uris: string, consent_required: bool, backchannel_logout_uri: string}
     */
    private function clientValues(FormData $data, ?Client $client): array
    {
        $stored = [
            'name' => $client->name ?? '',
            'token_endpoint_auth_method' => $client?->token_endpoint_auth_method->value ?? TokenEndpointAuthMethod::ClientSecretBasic->value,
            'grant_types' => $client->grant_types ?? [],
            'redirect_uris' => implode("\n", $client->redirect_uris ?? []),
            'post_logout_redirect_uris' => implode("\n", $client->post_logout_redirect_uris ?? []),
            'consent_required' => $client->consent_required ?? true,
            'backchannel_logout_uri' => $client->backchannel_logout_uri ?? '',
        ];

        $merged = [...$stored, ...$data->only(array_keys($stored))];

        return [
            'name' => (string) $merged['name'],
            'token_endpoint_auth_method' => (string) $merged['token_endpoint_auth_method'],
            'grant_types' => array_values(array_filter((array) $merged['grant_types'], is_string(...))),
            'redirect_uris' => (string) $merged['redirect_uris'],
            'post_logout_redirect_uris' => (string) $merged['post_logout_redirect_uris'],
            'consent_required' => (bool) $merged['consent_required'],
            'backchannel_logout_uri' => (string) $merged['backchannel_logout_uri'],
        ];
    }

    /**
     * The whole-client check, attached to every field that can break it. Each
     * problem is reported on the field that owns it, and on the field being
     * edited when the owning one is not part of this submission.
     */
    private function clientConsistencyRule(FormData $data, ?Client $client): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($data, $client): void {
            $values = $this->clientValues($data, $client);
            $grantTypes = $values['grant_types'];
            $method = TokenEndpointAuthMethod::tryFrom($values['token_endpoint_auth_method']);

            $report = function (string $owner, string $message) use ($attribute, $data, $fail): void {
                if ($attribute === $owner || ! $data->has($owner)) {
                    $fail($message);
                }
            };

            if (in_array('refresh_token', $grantTypes, true) && ! in_array('authorization_code', $grantTypes, true)) {
                $report('grant_types', __('admin.clients.validation.refresh-token-needs-code'));
            }

            if (in_array('client_credentials', $grantTypes, true) && $method instanceof TokenEndpointAuthMethod && ! $method->requiresSecret()) {
                $report('grant_types', __('admin.clients.validation.client-credentials-needs-secret'));
            }

            if (in_array('authorization_code', $grantTypes, true) && $this->lines($values['redirect_uris']) === []) {
                $report('redirect_uris', __('admin.clients.validation.redirect-uri-required'));
            }
        };
    }

    private function uriListRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            foreach ($this->lines((string) $value) as $uri) {
                $parts = parse_url($uri);

                if (! is_array($parts) || (string) ($parts['scheme'] ?? '') === '' || (string) ($parts['host'] ?? '') === '' || array_key_exists('fragment', $parts)) {
                    $fail(__('admin.clients.validation.invalid-uri', ['uri' => $uri]));
                }
            }
        };
    }

    /**
     * @return list<string>
     */
    private function lines(string $value): array
    {
        return array_values(array_unique(array_filter(
            array_map(trim(...), preg_split('/\R/u', $value) ?: []),
            fn (string $line): bool => $line !== '',
        )));
    }
}
