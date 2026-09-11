<?php

declare(strict_types=1);

namespace App\Shared\Realms\Enums;

enum RealmSettingSection: string
{
    case Login = 'login';
    case Tokens = 'tokens';
    case Sessions = 'sessions';
    case Mfa = 'mfa';
    case Passwords = 'passwords';
    case Clients = 'clients';
}
