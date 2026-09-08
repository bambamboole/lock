<?php
declare(strict_types=1);

namespace App\Auth\Concerns;

use Illuminate\Support\Str;

trait HasPermissionLabel
{
    /**
     * Translation key for this permission case's description: the enum's domain
     * (class basename minus the `Permission` suffix, kebab-cased) followed by
     * the value with `:` turned into `.` — e.g. `TenantPermission::ViewAudit`
     * => `tenant.permissions.audit.view`.
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

    /**
     * A permission's label is already the sentence that describes it — the
     * roles UI and the OAuth consent screen show the same string.
     */
    public function getDescription(): string
    {
        return $this->getLabel();
    }
}
