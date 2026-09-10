<?php

declare(strict_types=1);

namespace App\Auth\Support;

use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmRepository;
use Bambamboole\LaravelOidc\Server\Shared\Realms\RealmResolver;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final readonly class DatabaseRealmResolver implements RealmResolver
{
    public function __construct(private RealmRepository $realms) {}

    public function current(): Realm
    {
        $id = request()->attributes->get('oidc.realm') ?? (request()->routeIs('oidc.*', 'identity.*', 'account', 'realm.lattice.*') ? request()->route('realm') : null);

        if (! is_string($id) || $id === '') {
            $context = Context::get('realm_id');

            if (is_string($context)) {
                return Realm::query()->with('storedSettings')->findOrFail($context);
            }

            $id = (string) config('oidc.realm', 'admin');
        }

        $realm = $this->realms->find($id);

        return $realm instanceof Realm ? $realm : throw new NotFoundHttpException("Unknown realm [{$id}].");
    }
}
