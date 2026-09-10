<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Shared\Realms\Data\ClientAttributes;
use Bambamboole\LaravelOidc\Server\Clients\Enums\TokenEndpointAuthMethod;
use Bambamboole\LaravelOidc\Server\Clients\Models\Client;
use Closure;
use Illuminate\Validation\Rule;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\Components\Select;
use Lattice\Form\Components\Textarea;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Ui\Components\Component;

trait BuildsClientFields
{
    /**
     * @return array<int, Component>
     */
    private function clientFields(?Client $client): array
    {
        return [
            TextInput::make('name', __('admin.clients.fields.name.label'))
                ->value($client?->name, editable: true)
                ->required()->rules(['string', 'max:255']),
            Select::make('token_endpoint_auth_method', __('admin.clients.fields.type.label'))
                ->value($client?->token_endpoint_auth_method->value ?? TokenEndpointAuthMethod::ClientSecretBasic->value, editable: true)
                ->options(array_map(
                    fn (TokenEndpointAuthMethod $method): mixed => Select::option(__('admin.clients.types.'.str_replace('_', '-', $method->value)), $method->value),
                    TokenEndpointAuthMethod::cases(),
                ))
                ->helperText(__('admin.clients.fields.type.help-text'))
                ->required()->rules([Rule::enum(TokenEndpointAuthMethod::class)]),
            CheckboxGroup::make('grant_types', __('admin.clients.fields.grant-types.label'))
                ->value($client->grant_types ?? ['authorization_code', 'refresh_token'], editable: true)
                ->options(array_map(
                    fn (string $grantType): mixed => CheckboxGroup::option(__('admin.clients.grant-types.'.str_replace('_', '-', $grantType)), $grantType),
                    ClientAttributes::GRANT_TYPES,
                ))
                ->helperText(__('admin.clients.fields.grant-types.help-text'))
                ->required()->rules(['array', 'min:1', Rule::in(ClientAttributes::GRANT_TYPES)])
                ->rules(fn (FormData $data): array => [$this->grantTypesRule($data)]),
            Textarea::make('redirect_uris', __('admin.clients.fields.redirect-uris.label'))
                ->value(implode("\n", $client->redirect_uris ?? []), editable: true)->rows(3)
                ->helperText(__('admin.clients.fields.redirect-uris.help-text'))
                ->rules(['nullable', 'string', 'max:20000'])
                ->rules(fn (FormData $data): array => [
                    Rule::requiredIf(fn (): bool => in_array('authorization_code', (array) $data->get('grant_types', []), true)),
                    $this->uriListRule(),
                ])
                ->message('required', __('admin.clients.validation.redirect-uri-required')),
            Textarea::make('post_logout_redirect_uris', __('admin.clients.fields.post-logout-redirect-uris.label'))
                ->value(implode("\n", $client->post_logout_redirect_uris ?? []), editable: true)->rows(2)
                ->helperText(__('admin.clients.fields.post-logout-redirect-uris.help-text'))
                ->rules(['nullable', 'string', 'max:20000'])
                ->rules(fn (): array => [$this->uriListRule()]),
        ];
    }

    private function clientAttributes(FormData $data, bool $consentRequired = true, ?string $backchannelLogoutUri = null): ClientAttributes
    {
        /** @var list<string> $grantTypes */
        $grantTypes = array_values(array_filter((array) $data->get('grant_types', []), is_string(...)));

        return new ClientAttributes(
            name: (string) $data->string('name'),
            tokenEndpointAuthMethod: TokenEndpointAuthMethod::from((string) $data->string('token_endpoint_auth_method')),
            redirectUris: $this->lines((string) $data->string('redirect_uris')),
            postLogoutRedirectUris: $this->lines((string) $data->string('post_logout_redirect_uris')),
            grantTypes: $grantTypes,
            consentRequired: $consentRequired,
            backchannelLogoutUri: $backchannelLogoutUri,
        );
    }

    private function grantTypesRule(FormData $data): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) use ($data): void {
            $grantTypes = is_array($value) ? $value : [];
            $method = TokenEndpointAuthMethod::tryFrom((string) $data->string('token_endpoint_auth_method'));

            if (in_array('refresh_token', $grantTypes, true) && ! in_array('authorization_code', $grantTypes, true)) {
                $fail(__('admin.clients.validation.refresh-token-needs-code'));
            }

            if (in_array('client_credentials', $grantTypes, true) && $method instanceof TokenEndpointAuthMethod && ! $method->requiresSecret()) {
                $fail(__('admin.clients.validation.client-credentials-needs-secret'));
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
