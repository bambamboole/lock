<?php
declare(strict_types=1);

namespace App\Admin\Ui\Concerns;

use App\Realms\Models\Realm;
use App\Realms\Models\RealmSocialProvider;
use App\Shared\Realms\Enums\SocialProviderDriver;
use Illuminate\Validation\Rule;
use Lattice\Form\Components\Field;
use Lattice\Form\Components\PasswordInput;
use Lattice\Form\Components\Select;
use Lattice\Form\Components\Textarea;
use Lattice\Form\Components\TextInput;
use Lattice\Form\Components\Toggle;
use Lattice\Form\FormData;

trait BuildsSocialProviderFields
{
    /**
     * The driver decides which credentials a provider needs, so the create
     * form carries one field per credential across all drivers, each shown
     * and validated only for the drivers that use it.
     *
     * @return array<int, Field>
     */
    private function socialProviderCreationFields(Realm $realm): array
    {
        $fields = [
            TextInput::make('key', __('admin.social-providers.fields.key.label'))
                ->required()
                ->helperText(__('admin.social-providers.fields.key.help-text', ['url' => $realm->origin().'/auth/social/{key}/callback']))
                ->rules([
                    'string',
                    'max:64',
                    'regex:/^[a-z][a-z0-9-]*$/',
                    Rule::unique(RealmSocialProvider::class, 'key')->where('realm_id', $realm->id),
                ]),
            Select::make('driver', __('admin.social-providers.fields.driver.label'))
                ->value(SocialProviderDriver::Google->value, editable: true)
                ->options(array_map(
                    fn (SocialProviderDriver $driver): mixed => Select::option(__('admin.social-providers.drivers.'.$driver->value), $driver->value),
                    SocialProviderDriver::cases(),
                ))
                ->required()
                ->rules(['string', Rule::enum(SocialProviderDriver::class)]),
        ];

        foreach (SocialProviderDriver::credentials() as $name => $drivers) {
            $values = array_map(fn (SocialProviderDriver $driver): string => $driver->value, $drivers);

            $fields[] = $this->credentialField($name, null)
                ->visibleWhen('driver', $values)
                ->rules(['required_if:driver,'.implode(',', $values)]);
        }

        return [...$fields, $this->enabledField(true)];
    }

    /**
     * The rows of the provider's detail page, keyed the way its disclosure
     * forms name them.
     *
     * @return array<string, Field>
     */
    private function socialProviderFields(RealmSocialProvider $provider): array
    {
        $fields = [];

        foreach ($provider->driver->fields() as $name) {
            $fields[$name] = $this->credentialField($name, $provider);
        }

        return [...$fields, 'enabled' => $this->enabledField($provider->enabled)];
    }

    /**
     * @return array<string, string>
     */
    private function submittedCredentials(SocialProviderDriver $driver, FormData $data): array
    {
        $credentials = [];

        foreach ($driver->fields() as $field) {
            $credentials[$field] = trim((string) $data->string($field));
        }

        return $credentials;
    }

    private function credentialField(string $name, ?RealmSocialProvider $provider): Field
    {
        $key = str_replace('_', '-', $name);
        $label = __('admin.social-providers.fields.'.$key.'.label');
        $secret = SocialProviderDriver::isSecret($name);

        /*
         * Nullable throughout: the empty input for a credential another driver
         * owns arrives as null, and an empty secret means "keep the stored
         * one". What a driver does need is required by `required_if` on
         * creation and by the field itself on an edit.
         */
        $field = match (true) {
            $name === 'private_key' => Textarea::make($name, $label)->rows(6)->rules(['nullable', 'string', 'max:8000']),
            $secret => PasswordInput::make($name, $label)->rules(['nullable', 'string', 'max:1000']),
            default => TextInput::make($name, $label)->rules(['nullable', 'string', 'max:255']),
        };

        if ($secret) {
            return $provider instanceof RealmSocialProvider
                ? $field->helperText(__('admin.social-providers.fields.secret.help-text'))
                : $field->helperText(__('admin.social-providers.fields.'.$key.'.help-text'));
        }

        return $field
            ->value($provider?->config[$name] ?? null, editable: true)
            ->required($provider instanceof RealmSocialProvider)
            ->helperText(__('admin.social-providers.fields.'.$key.'.help-text'));
    }

    private function enabledField(bool $enabled): Field
    {
        return Toggle::make('enabled', __('admin.social-providers.fields.enabled.label'))
            ->value($enabled, editable: true)
            ->helperText(__('admin.social-providers.fields.enabled.help-text'));
    }
}
