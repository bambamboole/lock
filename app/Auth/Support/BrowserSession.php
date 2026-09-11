<?php

declare(strict_types=1);

namespace App\Auth\Support;

use Carbon\CarbonImmutable;
use stdClass;

/**
 * The OIDC session id is read out of the payload so ending a browser session
 * also revokes the provider session behind it.
 */
final readonly class BrowserSession
{
    public function __construct(
        public string $id,
        public ?string $ipAddress,
        public ?string $userAgent,
        public CarbonImmutable $lastActivity,
        private string $payload,
    ) {}

    public static function fromRow(stdClass $row): self
    {
        return new self(
            id: (string) $row->id,
            ipAddress: is_string($row->ip_address) ? $row->ip_address : null,
            userAgent: is_string($row->user_agent) ? $row->user_agent : null,
            lastActivity: CarbonImmutable::createFromTimestamp((int) $row->last_activity),
            payload: (string) $row->payload,
        );
    }

    public function browser(): ?string
    {
        $agent = (string) $this->userAgent;

        foreach ([
            'Edg/' => 'Microsoft Edge',
            'OPR/' => 'Opera',
            'Firefox/' => 'Firefox',
            'Chrome/' => 'Chrome',
            'Safari/' => 'Safari',
        ] as $needle => $name) {
            if (str_contains($agent, $needle)) {
                return $name;
            }
        }

        return null;
    }

    public function platform(): ?string
    {
        $agent = (string) $this->userAgent;

        foreach ([
            'iPhone' => 'iPhone',
            'iPad' => 'iPad',
            'Android' => 'Android',
            'Windows' => 'Windows',
            'Macintosh' => 'macOS',
            'CrOS' => 'ChromeOS',
            'Linux' => 'Linux',
        ] as $needle => $name) {
            if (str_contains($agent, $needle)) {
                return $name;
            }
        }

        return null;
    }

    public function oidcSessionId(): ?string
    {
        if (config('session.encrypt')) {
            return null;
        }

        $decoded = base64_decode($this->payload, true);

        if ($decoded === false) {
            return null;
        }

        $data = config('session.serialization') === 'json'
            ? json_decode($decoded, true)
            : @unserialize($decoded, ['allowed_classes' => false]);
        $sid = is_array($data) ? ($data['oidc']['sid'] ?? null) : null;

        return is_string($sid) && $sid !== '' ? $sid : null;
    }
}
