<?php
declare(strict_types=1);

namespace App\Auth\Ui\Actions;

use App\Auth\Enums\AuthAuditEvent;
use App\Auth\Models\OAuthAccessToken;
use App\Shared\Concerns\ResolvesCurrentUser;
use App\Tenants\Models\Tenant;
use App\Tenants\Support\Audit;
use Illuminate\Http\Request;
use Lattice\Actions\ActionDefinition;
use Lattice\Actions\ActionResult;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsAction;
use Lattice\Ui\Enums\HttpMethod;
use Lattice\Ui\Enums\Variant;

#[AsAction('tenant.settings.api-tokens.revoke')]
final class RevokeApiToken extends ActionDefinition
{
    use ResolvesCurrentUser;

    public function definition(Action $action): Action
    {
        return $action
            ->label(__('tenant.settings.api-tokens.revoke.label'))
            ->method(HttpMethod::Delete)
            ->variant(Variant::Danger)
            ->confirm(
                title: __('tenant.settings.api-tokens.revoke.confirm.title'),
                description: __('tenant.settings.api-tokens.revoke.confirm.description'),
                confirmLabel: __('tenant.settings.api-tokens.revoke.confirm.label'),
            );
    }

    #[\Override]
    public function authorize(Request $request): bool
    {
        $token = OAuthAccessToken::query()->find($this->tokenId());

        return $token !== null && $token->user_id === $this->currentUser()->id;
    }

    public function handle(Request $request): ActionResult
    {
        $token = OAuthAccessToken::query()
            ->where('user_id', $this->currentUser()->id)
            ->findOrFail($this->tokenId());

        $tenant = $token->tenant;
        $token->revoke();

        if ($tenant instanceof Tenant) {
            Audit::record(AuthAuditEvent::ApiTokenRevoked, $token, $tenant, [
                'name' => $token->name,
            ]);
        }

        return ActionResult::success()
            ->toast(__('tenant.settings.api-tokens.revoked'), Variant::Success)
            ->reloadComponent('tenant.settings.api-tokens');
    }

    private function tokenId(): string
    {
        $tokenId = $this->context('token_id');

        abort_unless(is_string($tokenId) && $tokenId !== '', 404);

        return $tokenId;
    }
}
