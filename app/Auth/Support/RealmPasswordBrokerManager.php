<?php
declare(strict_types=1);

namespace App\Auth\Support;

use Illuminate\Auth\Passwords\PasswordBrokerManager;
use Illuminate\Auth\Passwords\TokenRepositoryInterface;
use Illuminate\Contracts\Config\Repository as Config;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Database\DatabaseManager;
use InvalidArgumentException;

/**
 * The broker manager offers no hook for swapping the token repository, so the
 * one factory method that builds it is overridden instead.
 */
final class RealmPasswordBrokerManager extends PasswordBrokerManager
{
    /**
     * @param  array<string, mixed>  $config
     */
    protected function createTokenRepository(array $config): TokenRepositoryInterface
    {
        if (($config['driver'] ?? null) === 'cache') {
            throw new InvalidArgumentException('The cache password broker driver has no realm-aware equivalent.');
        }

        $connection = $this->app->make(DatabaseManager::class)
            ->connection(is_string($config['connection'] ?? null) ? $config['connection'] : null);

        return new RealmTokenRepository(
            $connection,
            $this->app->make(Hasher::class),
            (string) $config['table'],
            $this->hashKey(),
            ((int) ($config['expire'] ?? 60)) * 60,
            (int) ($config['throttle'] ?? 0),
        );
    }

    private function hashKey(): string
    {
        $key = (string) $this->app->make(Config::class)->get('app.key');

        return str_starts_with($key, 'base64:')
            ? (string) base64_decode(substr($key, 7), true)
            : $key;
    }
}
