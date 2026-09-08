<?php
declare(strict_types=1);

namespace App\Realms\Http\Requests;

use App\Auth\Models\User;
use App\Realms\Models\RealmInvitation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AcceptRealmInvitationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invitation' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                $user = $this->user();

                if (! $value instanceof RealmInvitation || ! $user instanceof User || strtolower($value->email) !== strtolower($user->email)) {
                    $fail(__('realm.accept.wrong-email'));
                } elseif ($value->isAccepted()) {
                    $fail(__('realm.accept.already-accepted'));
                } elseif ($value->isExpired()) {
                    $fail(__('realm.accept.expired'));
                }
            }],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        return array_merge(parent::validationData(), [
            'invitation' => $this->route('invitation'),
        ]);
    }
}
