<?php
declare(strict_types=1);

namespace App\Resources\Ui\Concerns;

use App\Realms\Models\Realm;
use App\Resources\Models\Resource;
use Closure;
use Illuminate\Validation\Rule;
use Lattice\Form\Components\TextInput;

trait BuildsResourceFields
{
    /**
     * The identifier is the RFC 8707 `resource` a client asks for: a path
     * relative to the realm issuer, or an absolute URI naming a resource server
     * the realm does not host.
     *
     * @return array<int, TextInput>
     */
    private function resourceFields(Realm $realm, ?Resource $resource): array
    {
        $unique = Rule::unique(Resource::class, 'identifier')->where('realm_id', $realm->id);

        return [
            TextInput::make('identifier', __('admin.resources.fields.identifier.label'))
                ->value($resource?->identifier, editable: true)
                ->required()
                ->helperText(__('admin.resources.fields.identifier.help-text'))
                ->rules(['string', 'max:255', $resource instanceof Resource ? $unique->ignore($resource) : $unique])
                ->rules(fn (): array => [$this->identifierRule()]),
            TextInput::make('name', __('admin.resources.fields.name.label'))
                ->value($resource?->name, editable: true)
                ->required()
                ->rules(['string', 'max:255']),
        ];
    }

    private function identifierRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail): void {
            $identifier = trim((string) $value);

            if (parse_url($identifier, PHP_URL_SCHEME) === null) {
                if (preg_match('/^[A-Za-z0-9][A-Za-z0-9._~-]*(\/[A-Za-z0-9._~-]+)*$/', $identifier) !== 1) {
                    $fail(__('admin.resources.validation.identifier-format'));
                }

                return;
            }

            $parts = parse_url($identifier);

            if (! is_array($parts) || (string) ($parts['host'] ?? '') === '' || array_key_exists('fragment', $parts)) {
                $fail(__('admin.resources.validation.identifier-uri'));
            }
        };
    }
}
