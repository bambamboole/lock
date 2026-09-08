<?php
declare(strict_types=1);

namespace App\Auth\Models;

use App\Tenants\Models\Tenant;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Token;

final class OAuthAccessToken extends Token
{
    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
