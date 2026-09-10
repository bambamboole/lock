<?php
declare(strict_types=1);

namespace App\Admin\Ui\Forms;

use App\Admin\Enums\AdminPermission;
use App\Realms\Models\Realm;
use App\Shared\Realms\Contracts\UpdatesRealmConfiguration;
use App\Shared\Realms\Support\RealmConfiguration;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Lattice\Facades\Effects;
use Lattice\Form\Attributes\AsForm;
use Lattice\Form\Components\Checkbox;
use Lattice\Form\Components\CheckboxGroup;
use Lattice\Form\Components\Form as FormComponent;
use Lattice\Form\Components\NumberInput;
use Lattice\Form\Components\Textarea;
use Lattice\Form\Components\TextInput;
use Lattice\Form\FormData;
use Lattice\Form\FormDefinition;
use Lattice\Http\LatticeResponse;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Grid;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsForm('admin.realms.configuration', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class UpdateRealmConfigurationForm extends FormDefinition
{
    private const array LIST_FIELDS = ['allowed_redirect_schemes', 'allowed_redirect_domains', 'default_scopes', 'trusted_clients'];

    public function __construct(private readonly UpdatesRealmConfiguration $updateRealm) {}

    public function definition(FormComponent $form, Request $request): FormComponent
    {
        $realm = $this->realm();
        $configuration = $realm->configuration();

        return $form->method(HttpMethod::Patch)->schema([
            Card::make(__('admin.realms.sections.tokens'))->schema([
                Grid::make('realm-token-fields')->columns(2)->schema([
                    $this->number('access_token_lifetime', $configuration),
                    $this->number('id_token_lifetime', $configuration),
                    $this->number('client_credentials_lifetime', $configuration),
                    $this->number('refresh_token_lifetime', $configuration),
                ]),
            ]),
            Card::make(__('admin.realms.sections.sessions'))->schema([
                Grid::make('realm-session-fields')->columns(2)->schema([
                    TextInput::make('session_cookie_name', __('admin.realms.fields.session-cookie-name.label'))
                        ->value($configuration['session_cookie_name'], editable: true)
                        ->rules(RealmConfiguration::rules()['session_cookie_name'])
                        ->helperText(__('admin.realms.fields.session-cookie-name.help-text')),
                    $this->number('session_absolute_lifetime', $configuration),
                    $this->number('session_token_ttl', $configuration),
                    $this->number('session_token_refresh_skew', $configuration),
                ]),
            ]),
            Card::make(__('admin.realms.sections.mfa'))->schema([
                CheckboxGroup::make('challenge_providers', __('admin.realms.fields.challenge-providers.label'))
                    ->value($configuration['challenge_providers'], editable: true)
                    ->rules(RealmConfiguration::rules()['challenge_providers'])
                    ->options([
                        CheckboxGroup::option(__('admin.realms.factors.totp'), 'totp'),
                        CheckboxGroup::option(__('admin.realms.factors.webauthn'), 'webauthn'),
                    ])->helperText(__('admin.realms.fields.challenge-providers.help-text')),
                Grid::make('realm-mfa-fields')->columns(2)->schema([
                    $this->number('totp_secret_length', $configuration),
                    $this->number('totp_window', $configuration),
                    $this->number('recovery_codes', $configuration),
                ]),
            ]),
            Card::make(__('admin.realms.sections.clients'))->schema([
                $this->checkbox('dynamic_registration', $configuration),
                $this->checkbox('token_exchange', $configuration),
                $this->checkbox('first_party_trusted', $configuration),
                ...array_map(fn (string $name): Textarea => $this->stringList($name, $configuration), self::LIST_FIELDS),
            ]),
        ])->submitLabel(__('common.action.save'));
    }

    public function handle(FormData $data): LatticeResponse
    {
        $realm = $this->realm();
        $configuration = $data->except('name', 'slug');

        foreach (self::LIST_FIELDS as $name) {
            $configuration[$name] = array_values(array_filter(
                array_map(trim(...), preg_split('/\R/u', (string) $data->string($name)) ?: []),
                fn (string $line): bool => $line !== '',
            ));
        }

        try {
            $this->updateRealm->handle($realm, $configuration);
        } catch (ValidationException $exception) {
            $errors = [];

            foreach ($exception->errors() as $key => $messages) {
                $field = explode('.', $key)[0];
                $key = in_array($field, self::LIST_FIELDS, true) ? $field : $key;
                $errors[$key] = [...($errors[$key] ?? []), ...$messages];
            }

            throw ValidationException::withMessages($errors);
        }

        return Effects::respond()->toast(__('admin.realms.configuration-saved'), Variant::Success)
            ->toRoute('admin.realms.show', ['realm' => $realm->slug, 'tabs' => 'configuration']);
    }

    /** @param array<string, mixed> $configuration */
    private function number(string $name, array $configuration): NumberInput
    {
        $key = str_replace('_', '-', $name);

        return NumberInput::make($name, __("admin.realms.fields.{$key}.label"))
            ->value($configuration[$name], editable: true)->step($name === 'totp_secret_length' ? 8 : 1)->required()
            ->rules(RealmConfiguration::rules()[$name])
            ->helperText(__("admin.realms.fields.{$key}.help-text"));
    }

    /** @param array<string, mixed> $configuration */
    private function checkbox(string $name, array $configuration): Checkbox
    {
        $key = str_replace('_', '-', $name);

        return Checkbox::make($name, __("admin.realms.fields.{$key}.label"))
            ->value($configuration[$name], editable: true)->rules(RealmConfiguration::rules()[$name])
            ->helperText(__("admin.realms.fields.{$key}.help-text"));
    }

    /** @param array<string, mixed> $configuration */
    private function stringList(string $name, array $configuration): Textarea
    {
        $key = str_replace('_', '-', $name);
        $values = $configuration[$name];

        return Textarea::make($name, __("admin.realms.fields.{$key}.label"))
            ->value(implode("\n", is_array($values) ? $values : []), editable: true)->rows(3)
            ->rules(['nullable', 'string', 'max:20000'])
            ->helperText(__("admin.realms.fields.{$key}.help-text"));
    }

    private function realm(): Realm
    {
        $realm = $this->contextModel('realm');

        abort_unless($realm instanceof Realm, 404);

        return $realm;
    }
}
