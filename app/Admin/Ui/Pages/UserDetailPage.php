<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\ResendUserVerification;
use App\Admin\Ui\Actions\SendUserPasswordReset;
use App\Admin\Ui\Actions\UpdateUserGlobalRoles;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
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

#[AsPage(route: '/admin/realms/{realm}/users/{user}', name: 'admin.realms.users.show', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UserDetailPage extends AdminPage
{
    private ?User $user = null;

    public function title(): ?string
    {
        return $this->user?->name;
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->user instanceof User) {
            return [];
        }

        $realm = $this->user->realm;

        return [
            Breadcrumb::make($realm->name, route('admin.realms.users', ['realm' => $realm->slug], false)),
            Breadcrumb::make(__('navigation.users'), route('admin.realms.users', ['realm' => $realm->slug], false)),
            Breadcrumb::make($this->user->name, route('admin.realms.users.show', ['realm' => $realm->slug, 'user' => $this->user->id], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm, User $user, Request $request): PageSchema
    {
        abort_unless($user->belongsToRealm($realm), 404);

        $this->user = $user;
        $user->setRelation('realm', $realm);
        $currentUser = $request->user();

        return $schema->schema([
            Stack::make('admin-user-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Medium)
                ->schema([
                    PageHeader::make($user->name),
                    $this->summaryCard($user),
                    $this->realmCard($user),
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

    private function realmCard(User $user): Card
    {
        return Card::make(__('admin.users.detail.realms.heading'))->schema([
            DescriptionList::make('user-realm')->bleed()->schema([
                TextEntry::make('realm', __('admin.users.detail.realm'))->value($user->realm->name),
            ]),
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
