<?php
declare(strict_types=1);

namespace App\Shared\Auth\Concerns;

use Illuminate\Support\Str;

trait HasPermissionLabel
{
    /**
     * The enum's domain (class basename minus `Permission`, kebab-cased)
     * followed by the value with `:` turned into `.`, e.g.
     * `AdminPermission::ManageUsers` => `admin.permissions.users.manage`.
     */
    public function getTranslationKey(): string
    {
        $domain = Str::kebab(Str::before(class_basename(static::class), 'Permission'));

        return "{$domain}.permissions.".str_replace(':', '.', (string) $this->value);
    }

    public function getLabel(): string
    {
        return __($this->getTranslationKey());
    }

    public function getDescription(): string
    {
        return $this->getLabel();
    }
}
