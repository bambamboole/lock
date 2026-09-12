<?php
declare(strict_types=1);

namespace App\Realms\Http\Api\V1\Requests;

use App\Realms\Models\Realm;
use App\Shared\Realms\Support\RealmConfiguration;
use App\Shared\Realms\Support\RealmDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
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
            /** Any subset of the realm's policy; settings left out keep their current value. */
            'configuration' => ['sometimes', Rule::array(array_keys(RealmConfiguration::defaults()))],
            ...$this->configurationRules(),
        ];
    }

    /**
     * Each setting validates on its own here, so an unknown key or a wrong type
     * is refused before the domain is touched and the reference documents what
     * every setting accepts. A rule comparing one setting against another is
     * left out: only the update, which merges the submission into the realm's
     * current configuration, can check those.
     *
     * @return array<string, list<mixed>>
     */
    private function configurationRules(): array
    {
        $rules = [];
        $all = RealmConfiguration::rules();

        foreach (RealmConfiguration::settings() as $setting) {
            $rules['configuration.'.$setting->name] = ['sometimes', ...$setting->ownRules()];

            if (array_key_exists($setting->name.'.*', $all)) {
                $rules['configuration.'.$setting->name.'.*'] = $all[$setting->name.'.*'];
            }
        }

        return $rules;
    }

    private function realm(): Realm
    {
        $realm = $this->route('realm');

        return $realm instanceof Realm ? $realm : throw new LogicException('The realm route parameter is not bound.');
    }
}
