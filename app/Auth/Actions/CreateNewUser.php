<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Concerns\PasswordValidationRules;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Exceptions\MissingRealmContext;
use Bambamboole\LaravelOidc\Server\Shared\Users\CreateUser;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateNewUser implements CreateUser
{
    use PasswordValidationRules;

    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(array $input): User
    {
        // Registration lands in the realm named by the request path. Uniqueness
        // follows the realm: the same address may already exist in a different one.
        $realm = Realm::fromContext() ?? throw new MissingRealmContext;

        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique(User::class)->where('realm_id', $realm->id),
            ],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(fn (): User => User::create([
            'realm_id' => $realm->id,
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => $input['password'],
        ]));
    }
}
