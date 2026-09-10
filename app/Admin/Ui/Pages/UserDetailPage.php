<?php

declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\BlockUserAction;
use App\Admin\Ui\Actions\DeleteUserAction;
use App\Admin\Ui\Actions\EndAllUserSessionsAction;
use App\Admin\Ui\Actions\ResendUserVerification;
use App\Admin\Ui\Actions\ResetUserMfaAction;
use App\Admin\Ui\Actions\SendUserPasswordReset;
use App\Admin\Ui\Actions\UnblockUserAction;
use App\Admin\Ui\Actions\UpdateUserGlobalRoles;
use App\Admin\Ui\Actions\UpdateUserRealmRoles;
use App\Admin\Ui\Forms\UpdateUserForm;
use App\Admin\Ui\Tables\UserSessionsTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Core\Enums\ColorName;
use Lattice\Form\Components\Form;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Badge;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\BadgeEntry;
use Lattice\Ui\Components\Entries\ComponentEntry;
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Icon as IconComponent;
use Lattice\Ui\Components\Link;
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
        $context = ['realm' => $realm->slug, 'user' => $user->id];

        return $schema->schema([
            Stack::make('admin-user-detail-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make($user->name, null, [
                        Badge::make(__('admin.users.status.blocked'))->color(ColorName::Danger)->visible($user->isBlocked()),
                    ]),
                    $this->summaryCard($user),
                    $this->profileCard($context),
                    $this->adminRolesCard($user, $currentUser),
                    $this->realmRolesCard($user, $context),
                    $this->accountActionsCard($user, $context),
                    $this->sessionsCard($context),
                    $this->dangerCard($context),
                ]),
        ]);
    }

    private function summaryCard(User $user): Card
    {
        return Card::make(__('admin.users.detail.summary.heading'))->schema([
            DescriptionList::make('user-summary')->bleed()->schema([
                TextEntry::make('name', __('common.field.name'))->value($user->name),
                ComponentEntry::make('email', __('common.field.email-address'))->value($this->emailValue($user)),
                TextEntry::make('realm', __('admin.users.detail.realm'))->value($user->realm->name),
                BadgeEntry::make('status', __('admin.users.detail.status'))
                    ->value($user->isBlocked() ? __('admin.users.status.blocked') : __('admin.users.status.active'))
                    ->color($user->isBlocked() ? ColorName::Danger : ColorName::Success),
                BadgeEntry::make('mfa', __('admin.users.detail.mfa'))
                    ->value($user->hasSecondFactor() ? __('admin.users.status.mfa-enabled') : __('admin.users.status.mfa-disabled'))
                    ->color($user->hasSecondFactor() ? ColorName::Success : ColorName::Muted),
                DateEntry::make('blocked_at', __('admin.users.detail.blocked-at'))
                    ->value($user->blocked_at?->toIso8601String())
                    ->style(DateTimeStyle::Long)
                    ->visible($user->isBlocked()),
                DateEntry::make('created_at', __('admin.users.detail.created-at'))
                    ->value($user->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }

    /**
     * @param  array<string, string>  $context
     */
    private function profileCard(array $context): Card
    {
        return Card::make(__('admin.users.detail.profile.heading'), __('admin.users.detail.profile.subtitle'))->schema([
            Form::use(UpdateUserForm::class, $context),
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

    /**
     * @param  array<string, string>  $context
     */
    private function realmRolesCard(User $user, array $context): Card
    {
        $realm = $user->realm;
        $names = $user->realmRoleNames();

        return Card::make(__('admin.users.detail.realm-roles.heading'), __('admin.users.detail.realm-roles.subtitle'))->schema([
            Stack::make()->gap(Gap::Medium)->schema([
                Stack::make('user-realm-roles')
                    ->direction(Orientation::Horizontal)
                    ->gap(Gap::Small)
                    ->schema($names === []
                        ? [Text::make(__('admin.users.detail.realm-roles.none'))->color(ColorName::Muted)]
                        : array_map(fn (string $name): Badge => Badge::make($name), $names)),
                $realm->realmRoles()->exists()
                    ? Action::use(UpdateUserRealmRoles::class, $context)
                    : Link::make(__('admin.users.detail.realm-roles.define'))->href(route('admin.realms.roles', ['realm' => $realm->slug], false)),
            ]),
        ]);
    }

    /**
     * @param  array<string, string>  $context
     */
    private function accountActionsCard(User $user, array $context): Card
    {
        return Card::make(__('admin.users.detail.account-actions.heading'))->schema([
            Stack::make()
                ->direction(Orientation::Horizontal)
                ->gap(Gap::Small)
                ->schema([
                    Action::use(ResendUserVerification::class, $context)
                        ->visible(! $user->hasVerifiedEmail() && ! $user->isBlocked()),
                    Action::use(SendUserPasswordReset::class, $context)
                        ->visible(! $user->isBlocked()),
                    Action::use(ResetUserMfaAction::class, $context)
                        ->visible($user->hasSecondFactor()),
                    Action::use(BlockUserAction::class, $context)
                        ->visible(! $user->isBlocked()),
                    Action::use(UnblockUserAction::class, $context)
                        ->visible($user->isBlocked()),
                ]),
        ]);
    }

    /**
     * @param  array<string, string>  $context
     */
    private function sessionsCard(array $context): Card
    {
        return Card::make(__('admin.users.detail.sessions.heading'), __('admin.users.detail.sessions.subtitle'))->schema([
            Stack::make()->gap(Gap::Medium)->schema([
                Table::use(UserSessionsTable::class, $context),
                Stack::make()
                    ->direction(Orientation::Horizontal)
                    ->gap(Gap::Small)
                    ->schema([
                        Action::use(EndAllUserSessionsAction::class, $context),
                    ]),
            ]),
        ]);
    }

    /**
     * @param  array<string, string>  $context
     */
    private function dangerCard(array $context): Card
    {
        return Card::make(__('admin.users.detail.delete.heading'), __('admin.users.detail.delete.subtitle'))->schema([
            Action::use(DeleteUserAction::class, $context),
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
