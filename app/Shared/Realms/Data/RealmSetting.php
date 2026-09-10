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

    /**
     * The kebab-cased leaf the `admin.realms.fields.*` translations are keyed
     * by.
     */
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
     * The rules a form holding this setting alone can check. A rule comparing
     * it to another setting needs the whole configuration, so it is left to
     * the merged validation the update runs.
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

    private static function comparesAnotherSetting(string $rule): bool
    {
        [$name, $parameter] = array_pad(explode(':', $rule, 2), 2, null);

        return in_array($name, ['lt', 'lte', 'gt', 'gte', 'same', 'different'], true)
            && $parameter !== null
            && array_key_exists($parameter, RealmConfiguration::defaults());
    }
}
