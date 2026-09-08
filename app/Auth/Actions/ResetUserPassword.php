<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Concerns\PasswordValidationRules;
use App\Auth\Models\User;
use Illuminate\Support\Facades\Validator;

final class ResetUserPassword
{
    use PasswordValidationRules;

    /**
     * @param  array<string, string>  $input
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
