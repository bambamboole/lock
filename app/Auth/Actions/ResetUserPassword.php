<?php
declare(strict_types=1);

namespace App\Auth\Actions;

use App\Auth\Models\User;
use Bambamboole\LaravelOidc\Server\Shared\Users\ResetUserPassword as ResetUserPasswordContract;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\Validator;

final class ResetUserPassword implements ResetUserPasswordContract
{
    /**
     * @param  array<string, mixed>  $input
     */
    public function __invoke(CanResetPassword $user, array $input): void
    {
        abort_unless($user instanceof User, 422);

        Validator::make($input, [
            'password' => ['required', 'string', 'confirmed'],
        ])->validate();

        $user->forceFill([
            'password' => $input['password'],
        ])->save();
    }
}
