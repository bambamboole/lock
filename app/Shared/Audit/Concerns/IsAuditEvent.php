<?php
declare(strict_types=1);

namespace App\Shared\Audit\Concerns;

trait IsAuditEvent
{
    public function eventName(): string
    {
        return $this->value;
    }

    /**
     * The description column stores this key; translation happens at read
     * time in the viewer's locale (Activity::description).
     */
    public function translationKey(): string
    {
        return 'audit.events.'.$this->value;
    }
}
