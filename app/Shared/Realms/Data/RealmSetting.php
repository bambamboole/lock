<?php

declare(strict_types=1);

namespace App\Shared\Realms\Data;

use App\Shared\Realms\Enums\RealmSettingSection;
use App\Shared\Realms\Enums\RealmSettingType;
use App\Shared\Realms\Support\RealmConfiguration;
use Illuminate\Validation\Rules\In;

final readonly class RealmSetting
{
    public function __construct(
        public string $name,
        public RealmSettingSection $section,
        public RealmSettingType $type,
    ) {}

    public function translationKey(): string
    {
        return str_replace('_', '-', $this->name);
    }

    /** @return list<string|In> */
    public function rules(): array
    {
        return RealmConfiguration::rules()[$this->name];
    }

    /**
     * A rule comparing against another setting needs the whole configuration,
     * so it is left to the update's merged validation.
     *
     * @return list<string|In>
     */
    public function ownRules(): array
    {
        return array_values(array_filter(
            $this->rules(),
            fn (string|In $rule): bool => ! is_string($rule) || ! self::comparesAnotherSetting($rule),
        ));
    }

    /**
     * A validated value still carries the type its transport had — JSON sends a
     * duration a client typed as `"3600"`, an HTML form sends everything as a
     * string — while the stored configuration is typed and the validator
     * compares settings against each other by value and type. This is the one
     * place that says what type a setting holds.
     */
    public function cast(mixed $value): mixed
    {
        return match ($this->type) {
            RealmSettingType::Duration, RealmSettingType::Days, RealmSettingType::Count => (int) $value,
            RealmSettingType::Text, RealmSettingType::MfaRequirement => (string) $value,
            RealmSettingType::Flag => (bool) $value,
            RealmSettingType::Factors, RealmSettingType::LoginMethods, RealmSettingType::StringList => array_values(
                array_filter(is_array($value) ? $value : [], is_string(...)),
            ),
        };
    }

    private static function comparesAnotherSetting(string $rule): bool
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        return in_array($name, ['lt', 'lte', 'gt', 'gte', 'same', 'different'], true)
            && $parameter !== null
            && array_key_exists($parameter, RealmConfiguration::defaults());
    }
}
