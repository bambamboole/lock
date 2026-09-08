<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Concerns\PasswordValidationRules;
use App\Auth\Models\User;
use App\Tenants\Actions\CreateTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

final class CreateNewUser
{
    use PasswordValidationRules;

    public function __construct(private CreateTenant $createTenant) {}

    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => $this->passwordRules(),
        ])->validate();

        return DB::transaction(function () use ($input) {
            $user = User::create([
                'name' => $input['name'],
                'email' => $input['email'],
                'password' => $input['password'],
            ]);

            $this->createTenant->handle($user, $user->name."'s Tenant", isPersonal: true);

            return $user;
        });
    }
}
