<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\ResendUserVerification;
use App\Admin\Ui\Actions\SendUserPasswordReset;
use App\Admin\Ui\Actions\UpdateUserGlobalRoles;
use App\Auth\Models\User;
use App\Realms\Models\Role;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Enums\ColorName;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\ComponentEntry;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Icon as IconComponent;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Icon;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Size;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/users/{user}', name: 'admin.users.show', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UserDetailPage extends AdminPage
{
    private ?User $user = null;

    public function title(): ?string
    {
        return $this->user?->name;
    }

    public function render(PageSchema $schema, User $user, Request $request): PageSchema
    {
        $this->user = $user;
        $user->load('realm');
        $currentUser = $request->user();

        $roleLabelsByRealm = $user->roles()
            ->whereNotNull('roles.realm_id')
            ->get()
            ->groupBy('realm_id')
            ->map(fn (Collection $roles) => $roles->map(fn (Role $role) => $role->displayName())->implode(', '))
            ->all();

        return $schema->schema([
            Stack::make('admin-user-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Medium)
                ->schema([
                    PageHeader::make($user->name),
                    $this->summaryCard($user),
                    $this->membershipsCard($user, $roleLabelsByRealm),
                    $this->adminRolesCard($user, $currentUser),
                    $this->accountActionsCard($user),
                ]),
        ]);
    }

    private function summaryCard(User $user): Card
    {
        return Card::make(__('admin.users.detail.summary.heading'))->schema([
            DescriptionList::make('user-summary')->bleed()->schema([
                TextEntry::make('name', __('common.field.name'))->value($user->name),
                ComponentEntry::make('email', __('common.field.email-address'))->value($this->emailValue($user)),
                DateEntry::make('created_at', __('admin.users.detail.created-at'))
                    ->value($user->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }

    /**
     * @param  array<int, string>  $roleLabelsByRealm
     */
    private function membershipsCard(User $user, array $roleLabelsByRealm): Card
    {
        return Card::make(__('admin.users.detail.realms.heading'))->schema([
            DescriptionList::make('user-realms')
                ->bleed()
                ->emptyLabel(__('admin.users.detail.no-realms'))
                ->schema(
                    [
                        TextEntry::make('realm-'.$user->realm->id, $user->realm->name)
                            ->value($roleLabelsByRealm[$user->realm->id] ?? null)
                            ->placeholder(__('admin.users.detail.no-roles')),
                    ],
                ),
        ]);
    }

    private function adminRolesCard(User $user, ?User $currentUser): Card
    {
        return Card::make(__('admin.users.detail.admin-roles.heading'))
            ->visible($currentUser instanceof User && $user->realm->isMaster() && Gate::forUser($currentUser)->allows(AdminPermission::ManageAdminRoles) && $currentUser->isNot($user))
            ->schema([
                Action::use(UpdateUserGlobalRoles::class, ['user' => $user->id]),
            ]);
    }

    private function accountActionsCard(User $user): Card
    {
        return Card::make(__('admin.users.detail.account-actions.heading'))->schema([
            Stack::make()
                ->direction(Orientation::Horizontal)
                ->gap(Gap::Small)
                ->schema([
                    Action::use(ResendUserVerification::class, ['user' => $user->id])
                        ->visible(! $user->hasVerifiedEmail()),
                    Action::use(SendUserPasswordReset::class, ['user' => $user->id]),
                ]),
        ]);
    }

    private function emailValue(User $user): Component
    {
        $status = $user->hasVerifiedEmail()
            ? IconComponent::make(Icon::Check)->size(Size::Sm)->color(ColorName::Success)
            : IconComponent::make(Icon::X)->size(Size::Sm)->color(ColorName::Warning);

        return Stack::make()
            ->direction(Orientation::Horizontal)
            ->width(Width::Auto)
            ->align(Align::Center)
            ->gap(Gap::Small)
            ->schema([
                Text::make($user->email),
                $status,
            ]);
    }
}
