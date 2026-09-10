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
use App\Admin\Ui\Forms\UpdateUserEmailForm;
use App\Admin\Ui\Forms\UpdateUserNameForm;
use App\Admin\Ui\Forms\UpdateUserVerificationForm;
use App\Admin\Ui\Tables\UserSessionsTable;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\ActionBar;
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
use Lattice\Ui\Components\Entries\DateEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Link;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\DateTimeStyle;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Orientation;
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
                        ...($user->isBlocked() ? [Badge::make(__('admin.users.status.blocked'))->color(ColorName::Danger)] : []),
                        ...$this->headerActions($user, $context),
                    ]),
                    $this->profileCard($user, $context),
                    $this->adminRolesCard($user, $currentUser),
                    $this->realmRolesCard($user, $context),
                    $this->sessionsCard($context),
                ]),
        ]);
    }

    /**
     * @param  array<string, string>  $context
     */
    private function profileCard(User $user, array $context): Card
    {
        return Card::make(__('admin.users.detail.profile.heading'), __('admin.users.detail.profile.subtitle'))->schema([
            DescriptionList::make('user-profile')->bleed()->schema([
                TextEntry::make('name', __('common.field.name'), 'user-name')
                    ->value($user->name)
                    ->disclosure([Form::use(UpdateUserNameForm::class, $context)]),
                TextEntry::make('email', __('common.field.email-address'), 'user-email')
                    ->value($user->email)
                    ->disclosure([Form::use(UpdateUserEmailForm::class, $context)]),
                BadgeEntry::make('email_verified', __('admin.users.fields.email-verified.label'), 'user-email-verified')
                    ->value($user->hasVerifiedEmail() ? __('common.value.yes') : __('common.value.no'))
                    ->color($user->hasVerifiedEmail() ? ColorName::Success : ColorName::Warning)
                    ->disclosure([Form::use(UpdateUserVerificationForm::class, $context)]),
                TextEntry::make('realm', __('admin.users.detail.realm'), 'user-realm')->value($user->realm->name),
                BadgeEntry::make('status', __('admin.users.detail.status'), 'user-status')
                    ->value($user->isBlocked() ? __('admin.users.status.blocked') : __('admin.users.status.active'))
                    ->color($user->isBlocked() ? ColorName::Danger : ColorName::Success),
                BadgeEntry::make('mfa', __('admin.users.detail.mfa'), 'user-mfa')
                    ->value($user->hasSecondFactor() ? __('admin.users.status.mfa-enabled') : __('admin.users.status.mfa-disabled'))
                    ->color($user->hasSecondFactor() ? ColorName::Success : ColorName::Muted),
                DateEntry::make('blocked_at', __('admin.users.detail.blocked-at'), 'user-blocked-at')
                    ->value($user->blocked_at?->toIso8601String())
                    ->style(DateTimeStyle::Long)
                    ->visible($user->isBlocked()),
                DateEntry::make('created_at', __('admin.users.detail.created-at'), 'user-created-at')
                    ->value($user->created_at?->toIso8601String())
                    ->style(DateTimeStyle::Long),
            ]),
        ]);
    }

    private function adminRolesCard(User $user, ?User $currentUser): Card
    {
        return Card::make(__('admin.users.detail.admin-roles.heading'))
            ->visible($currentUser instanceof User && $user->realm->isMaster() && Gate::forUser($currentUser)->allows(AdminPermission::ManageAdminRoles) && $currentUser->isNot($user))
            ->schema([
                ActionBar::button(Action::use(UpdateUserGlobalRoles::class, ['user' => $user->id])),
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
                    ? ActionBar::button(Action::use(UpdateUserRealmRoles::class, $context))
                    : Link::make(__('admin.users.detail.realm-roles.define'))->href(route('admin.realms.roles', ['realm' => $realm->slug], false)),
            ]),
        ]);
    }

    /**
     * A support call is about a password or about locking someone out; every
     * other action is rarer than the noise it would add to the header.
     *
     * @param  array<string, string>  $context
     * @return array<int, Component>
     */
    private function headerActions(User $user, array $context): array
    {
        return ActionBar::make(
            primary: [
                ...($user->isBlocked() ? [] : [Action::use(SendUserPasswordReset::class, $context)]),
                ...($user->isBlocked()
                    ? [Action::use(UnblockUserAction::class, $context)]
                    : [Action::use(BlockUserAction::class, $context)]),
            ],
            overflow: [
                ...($user->hasVerifiedEmail() || $user->isBlocked() ? [] : [Action::use(ResendUserVerification::class, $context)]),
                ...($user->hasSecondFactor() ? [Action::use(ResetUserMfaAction::class, $context)] : []),
                Action::use(EndAllUserSessionsAction::class, $context),
                Action::use(DeleteUserAction::class, $context),
            ],
            key: 'admin-user',
        );
    }

    /**
     * @param  array<string, string>  $context
     */
    private function sessionsCard(array $context): Card
    {
        return Card::make(__('admin.users.detail.sessions.heading'), __('admin.users.detail.sessions.subtitle'))->schema([
            Table::use(UserSessionsTable::class, $context),
        ]);
    }
}
