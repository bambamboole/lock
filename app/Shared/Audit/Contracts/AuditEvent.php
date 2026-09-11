<?php
declare(strict_types=1);

namespace App\Shared\Audit\Contracts;

interface AuditEvent
{
    public function eventName(): string;

    public function translationKey(): string;
}
