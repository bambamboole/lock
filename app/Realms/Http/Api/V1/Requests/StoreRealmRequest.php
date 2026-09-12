<?php
declare(strict_types=1);

namespace App\Realms\Http\Api\V1\Requests;

use App\Shared\Realms\Support\RealmDomain;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRealmRequest extends FormRequest
{
    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            /** Human readable name shown to the realm's users. */
            'name' => ['required', 'string', 'max:255'],
            /** Stable identifier, lowercase and dash separated. It cannot be changed later. */
            'slug' => ['required', 'string', 'max:63', 'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/', Rule::unique('realms', 'slug')],
            /** The domain the realm is served from. Lock checks that it reaches this instance, but keeps the realm either way. */
            'domain' => RealmDomain::rules(),
        ];
    }
}
