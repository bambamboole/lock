<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\SigningKeys\SigningKeyStore;
use Bambamboole\LaravelOidc\Server\SigningKeys\SigningKeyGenerator;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Realm>
 */
final class RealmFactory extends Factory
{
    /** @var class-string<Realm> */
    protected $model = Realm::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            // Browsers resolve every `*.localhost` to the loopback address, so
            // a fixture realm is reachable in browser tests without DNS.
            'domain' => fn (array $attributes): string => $attributes['slug'].'.localhost',
        ];
    }

    /**
     * Signing keys are per realm and their `kid` is unique across the
     * instance, so every realm needs a keypair of its own before it can mint a
     * token or serve a JWKS — `App\Realms\Actions\CreateRealm` gives a real
     * realm one, and a fixture realm is no use without it.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Realm $realm): void {
            $realm->runAsCurrent(
                fn () => app(SigningKeyStore::class)->rotate(app(SigningKeyGenerator::class)->generate()),
            );
        });
    }
}
