<?php
declare(strict_types=1);

namespace App\Tenants\Http\Api\V1\Resources;

use App\Auth\Models\User;
use App\Tenants\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class MemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => $this->roles->map(fn (Role $role) => [
                'id' => $role->id,
                'label' => $role->displayName(),
                'preset' => $role->preset?->value,
            ])->values(),
        ];
    }
}
