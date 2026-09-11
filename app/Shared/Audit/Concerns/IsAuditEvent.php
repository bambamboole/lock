<?php
declare(strict_types=1);

namespace App\Shared\Audit\Concerns;

trait IsAuditEvent
{
    public function eventName(): string
    {
        return $this->value;
    }

    public function translationKey(): string
    {
        return 'audit.events.'.$this->value;
    }
}
