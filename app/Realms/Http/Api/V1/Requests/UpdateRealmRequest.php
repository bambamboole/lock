<?php
declare(strict_types=1);

namespace App\Realms\Http\Api\V1\Requests;

use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmDomain;
use Illuminate\Foundation\Http\FormRequest;
use LogicException;

final class UpdateRealmRequest extends FormRequest
{
    /**
     * The master realm is served from APP_URL's host, so its row stores no
     * domain and a request that carries one is rejected rather than ignored.
     *
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $realm = $this->realm();

        return [
            /** Human readable name shown to the realm's users. */
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            /** The domain the realm is served from. Re-pointing it changes the issuer and restarts the domain check. */
            'domain' => $realm->isMaster() ? ['prohibited'] : ['sometimes', ...RealmDomain::rules($realm)],
        ];
    }

    private function realm(): Realm
    {
        $realm = $this->route('realm');

        return $realm instanceof Realm ? $realm : throw new LogicException('The realm route parameter is not bound.');
    }
}
