<?php

declare(strict_types=1);

namespace App\Shared\Audit\Enums;

/**
 * The security events the OIDC server raises, mirrored here so the console can
 * filter and translate them. A row's `type` stays a plain string: a package
 * update may add a case this enum does not know yet, and such a row still has
 * to list.
 */
enum AuditEventType: string
{
    case LoginSucceeded = 'auth.login.succeeded';
    case LoginFailed = 'auth.login.failed';
    case LoggedOut = 'auth.logout';
    case PasswordReset = 'auth.password.reset';
    case RegistrationSucceeded = 'auth.registration.succeeded';
    case MfaChallengeSucceeded = 'auth.mfa.challenge_succeeded';
    case MfaChallengeFailed = 'auth.mfa.challenge_failed';
    case MfaFactorEnrollmentStarted = 'auth.mfa.factor_enrollment_started';
    case MfaFactorConfirmed = 'auth.mfa.factor_confirmed';
    case MfaFactorRevoked = 'auth.mfa.factor_revoked';
    case MfaRecoveryCodeUsed = 'auth.mfa.recovery_code_used';
    case TokenIssued = 'oauth.token.issued';
    case TokenFailed = 'oauth.token.failed';
    case TokenRevoked = 'oauth.token.revoked';
    case ConsentApproved = 'oauth.consent.approved';
    case ConsentDenied = 'oauth.consent.denied';
    case ClientAuthFailed = 'oauth.client_auth.failed';
    case ClientProvisioned = 'admin.client.provisioned';
    case ClientRegistered = 'admin.client.registered';
    case KeysRotated = 'admin.keys.rotated';

    public function category(): AuditEventCategory
    {
        return AuditEventCategory::from(explode('.', $this->value)[0]);
    }

    /**
     * Mirrors the `failure` flag the package sets on the record, so the console
     * can colour a type before a row is in hand.
     */
    public function isFailure(): bool
    {
        return match ($this) {
            self::LoginFailed,
            self::MfaChallengeFailed,
            self::TokenFailed,
            self::ConsentDenied,
            self::ClientAuthFailed => true,
            default => false,
        };
    }

    /**
     * The package spells its types with underscores; translation keys are
     * kebab-case throughout this app.
     */
    public function translationKey(): string
    {
        return 'audit.types.'.str_replace('_', '-', $this->value);
    }

    public function label(): string
    {
        return __($this->translationKey());
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $type) {
            $options[$type->value] = $type->label();
        }

        return $options;
    }
}
