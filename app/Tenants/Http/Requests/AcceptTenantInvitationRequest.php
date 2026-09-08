<?php
declare(strict_types=1);

namespace App\Tenants\Http\Requests;

use App\Auth\Models\User;
use App\Tenants\Models\TenantInvitation;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class AcceptTenantInvitationRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'invitation' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                $user = $this->user();

                if (! $value instanceof TenantInvitation || ! $user instanceof User || strtolower($value->email) !== strtolower($user->email)) {
                    $fail(__('tenant.accept.wrong-email'));
                } elseif ($value->isAccepted()) {
                    $fail(__('tenant.accept.already-accepted'));
                } elseif ($value->isExpired()) {
                    $fail(__('tenant.accept.expired'));
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
