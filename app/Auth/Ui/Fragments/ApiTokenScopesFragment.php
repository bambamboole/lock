<?php
declare(strict_types=1);

namespace App\Auth\Ui\Fragments;

use App\Auth\Models\OAuthAccessToken;
use App\Auth\Support\PermissionRepository;
use App\Shared\Concerns\ResolvesCurrentUser;
use Lattice\Core\Attributes\AsFragment;
use Lattice\Fragments\FragmentDefinition;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Size;
use Lattice\Ui\PageSchema;

#[AsFragment('tenant.settings.api-token-scopes')]
final class ApiTokenScopesFragment extends FragmentDefinition
{
    use ResolvesCurrentUser;

    public function __construct(private readonly PermissionRepository $permissions) {}

    public function schema(PageSchema $schema): PageSchema
    {
        $token = OAuthAccessToken::query()
            ->whereKey($this->context('token'))
            ->where('user_id', $this->currentUser()->id)
            ->where('revoked', false)
            ->first();

        $scopes = $token instanceof OAuthAccessToken ? (array) $token->scopes : [];

        if ($token === null || $scopes === []) {
            return $schema->schema([
                Text::make(__('tenant.settings.api-tokens.detail.empty')),
            ]);
        }

        $descriptions = $this->permissions->apiScopes();
        $lines = [];

        foreach ($scopes as $scope) {
            $scope = (string) $scope;
            $description = $descriptions[$scope] ?? null;
            $lines[] = Text::make($description === null ? $scope : "{$scope} — {$description}")->size(Size::Sm);
        }

        return $schema->schema([
            Stack::make('api-token-scopes-'.$token->getKey())->gap(Gap::Small)->schema($lines),
        ]);
    }
}
