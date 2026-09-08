<?php

declare(strict_types=1);

namespace App\Auth\Ui\Pages;

use App\Auth\Models\User;
use App\Auth\Ui\Actions\DeleteUserAccount;
use App\Auth\Ui\Forms\EmailForm;
use App\Auth\Ui\Forms\LanguageForm;
use App\Auth\Ui\Forms\NameForm;
use App\Auth\Ui\Forms\NotificationPreferencesForm;
use App\Auth\Ui\Forms\PasswordForm;
use App\Auth\Ui\Forms\TimezoneForm;
use App\Shared\Concerns\ResolvesCurrentUser;
use App\Shared\Ui\Components\PageHeader;
use Bambamboole\LaravelOidc\Server\Auth\MultiFactor\FactorRegistry;
use Bambamboole\LaravelOidc\Server\Auth\PasswordConfirmation;
use Bambamboole\LaravelOidc\Ui\Forms\TwoFactorSetupForm;
use Bambamboole\LaravelOidc\Ui\Tables\TwoFactorMethodsTable;
use Illuminate\Http\Request;
use Lattice\Actions\Components\Action;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Core\Enums\ColorName;
use Lattice\Core\Enums\PageLayout;
use Lattice\Form\Components\Form;
use Lattice\Http\Page;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Badge;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\DescriptionList;
use Lattice\Ui\Components\Entries\ComponentEntry;
use Lattice\Ui\Components\Entries\TextEntry;
use Lattice\Ui\Components\Heading;
use Lattice\Ui\Components\Icon as IconComponent;
use Lattice\Ui\Components\Modal;
use Lattice\Ui\Components\SegmentedControl;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Icon;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Size;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: 'profile', name: 'profile.edit', layout: PageLayout::App, middleware: ['web', 'auth'])]
final class ProfilePage extends Page
{
    use ResolvesCurrentUser;

    public function title(): string
    {
        return __('user.title');
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        return [Breadcrumb::make(__('user.title'), route('profile.edit', absolute: false))];
    }

    public function render(PageSchema $schema, Request $request, FactorRegistry $factors): PageSchema
    {
        $user = $this->currentUser();

        // The filtered list is what the login pipeline actually challenges on, so
        // it is also what may claim the account is protected. The unfiltered
        // check would call a passkey-only account protected while
        // `challenge_providers` still excluded webauthn.
        $twoFactorEnabled = $factors->configuredChallengeableEnrollments($user) !== [];
        $securityUnlocked = PasswordConfirmation::confirmedRecently($request->session());

        if (! $securityUnlocked) {
            $request->session()->put('url.intended', route('profile.edit'));
        }

        return $schema->schema([
            Stack::make('profile-page')
                ->gap(Gap::Large)
                ->width(Width::Medium)
                ->schema([
                    PageHeader::make(__('user.title'), __('user.profile.subtitle')),
                    $this->loginCard($user),
                    $this->preferencesCard($user, $this->currentAppearance($request)),
                    $this->securityCard($securityUnlocked, $twoFactorEnabled),
                    $this->dangerCard(),
                ]),
        ]);
    }

    private function loginCard(User $user): Card
    {
        return Card::make(__('user.sections.login'))->schema([
            DescriptionList::make('login-rows')->bleed()->schema([
                TextEntry::make('name', __('common.field.name'), 'row-name')
                    ->value($user->name)
                    ->disclosure([Form::use(NameForm::class)]),
                ComponentEntry::make('email', __('common.field.email-address'), 'row-email')
                    ->value($this->emailValue($user))
                    ->disclosure([Form::use(EmailForm::class)]),
                TextEntry::make('password', __('common.field.password'), 'row-password')
                    ->value('••••••••••')
                    ->disclosure([Form::use(PasswordForm::class)]),
            ]),
        ]);
    }

    private function emailValue(User $user): Component
    {
        $status = $user->hasVerifiedEmail()
            ? IconComponent::make(Icon::Check)->size(Size::Sm)->color(ColorName::Success)
            : Text::make(__('user.profile.email.unverified'))->size(Size::Sm)->color(ColorName::Warning);

        return Stack::make('row-email-value')
            ->direction(Orientation::Horizontal)
            ->width(Width::Auto)
            ->align(Align::Center)
            ->gap(Gap::Small)
            ->schema([
                Text::make($user->email),
                $status,
            ]);
    }

    private function securityCard(bool $unlocked, bool $twoFactorEnabled): Card
    {
        if (! $unlocked) {
            return Card::make(__('user.security.heading'), __('user.security.subtitle'))->schema([
                Text::make(__('user.security.confirm.description')),
                Button::make(__('user.security.confirm.action'))->href(route('identity.password.confirm', absolute: false)),
            ]);
        }

        return Card::make(__('user.security.heading'), __('user.security.subtitle'))->schema(
            $this->securityContent($twoFactorEnabled),
        );
    }

    /**
     * @return array<int, Component>
     */
    private function securityContent(bool $twoFactorEnabled): array
    {
        return [
            Stack::make('two-factor-authentication')
                ->gap(Gap::Small)
                ->schema([
                    Stack::make('two-factor-status')
                        ->direction(Orientation::Horizontal)
                        ->align(Align::Center)
                        ->gap(Gap::Small)
                        ->schema([
                            Heading::make(__('user.security.two-factor.heading'), 2),
                            $twoFactorEnabled
                                ? Badge::make(__('user.security.two-factor.status.enabled'))->color(ColorName::Success)
                                : Badge::make(__('user.security.two-factor.status.disabled'))->color(ColorName::Warning),
                        ]),
                    Text::make($twoFactorEnabled
                        ? __('user.security.two-factor.description.enabled')
                        : __('user.security.two-factor.description.disabled')),
                    // Every method, plus the recovery codes backing them. Removing
                    // the last one is how two-factor gets turned off; there is no
                    // separate switch to disagree with this list.
                    Table::lazy(TwoFactorMethodsTable::class),
                    Button::make(__('user.security.two-factor.add-method'))
                        ->icon(Icon::Plus)
                        ->modal(
                            Modal::make('oidc.two-factor-setup')
                                ->title(__('user.security.two-factor.setup-title'))
                                ->schema([
                                    Form::use(TwoFactorSetupForm::class),
                                ]),
                        ),
                ]),
        ];
    }

    private function preferencesCard(User $user, string $appearance): Card
    {
        return Card::make(__('user.preferences.heading'), __('user.preferences.subtitle'))->schema([
            DescriptionList::make('preference-rows')->bleed()->schema([
                ComponentEntry::make('appearance', __('user.preferences.theme.label'), 'row-appearance')
                    ->value(
                        SegmentedControl::make('appearance')
                            ->value($appearance)
                            ->emits('lattice:appearance-change')
                            ->options([
                                SegmentedControl::option(__('user.preferences.theme.light'), 'light'),
                                SegmentedControl::option(__('user.preferences.theme.dark'), 'dark'),
                                SegmentedControl::option(__('user.preferences.theme.system'), 'system'),
                            ]),
                    ),
                TextEntry::make('language', __('user.preferences.language.label'), 'row-language')
                    ->value(__('language.'.($user->locale ?? config('app.locale'))))
                    ->disclosure([Form::use(LanguageForm::class)]),
                TextEntry::make('timezone', __('user.preferences.timezone.label'), 'row-timezone')
                    ->value((string) ($user->timezone ?? config('app.timezone')))
                    ->disclosure([Form::use(TimezoneForm::class)]),
                TextEntry::make('notifications', __('user.preferences.notifications.heading'), 'row-notifications')
                    ->value($this->notificationsSummary($user))
                    ->disclosure([Form::use(NotificationPreferencesForm::class)]),
            ]),
        ]);
    }

    private function notificationsSummary(User $user): string
    {
        $enabled = collect([
            'notifications.realm-invitations',
            'notifications.realm-membership',
        ])->filter(fn (string $key) => $user->settings->get($key) !== false)->count();

        return __('user.preferences.notifications.summary', ['count' => $enabled]);
    }

    private function dangerCard(): Card
    {
        return Card::make(__('user.profile.delete-account.heading'))->schema([
            Text::make(__('user.profile.delete-account.description')),
            Action::use(DeleteUserAccount::class),
        ]);
    }

    private function currentAppearance(Request $request): string
    {
        $appearance = $request->cookie('appearance', 'system');

        return in_array($appearance, ['light', 'dark', 'system'], true) ? $appearance : 'system';
    }
}
