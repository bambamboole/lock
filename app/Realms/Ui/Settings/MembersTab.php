<?php

declare(strict_types=1);

namespace App\Realms\Ui\Settings;

use App\Auth\Models\User;
use App\Realms\Enums\RealmPermission;
use App\Realms\Models\Realm;
use App\Realms\Ui\Forms\InviteRealmMemberForm;
use App\Realms\Ui\Tables\RealmInvitationsTable;
use App\Realms\Ui\Tables\RealmMembersTable;
use Lattice\Form\Components\Form;
use Lattice\Table\Components\Table;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Tab;

final class MembersTab
{
    public function __invoke(Realm $realm, User $user): Tab
    {
        return Tab::make('members', __('realm.settings.tabs.members'))
            ->schema([
                Card::make(__('realm.settings.members.invite.heading'), __('realm.settings.members.invite.subtitle'))
                    ->schema([
                        Form::use(InviteRealmMemberForm::class),
                    ])
                    ->visible($user->can(RealmPermission::ManageMembers, $realm)),
                Card::make(__('realm.settings.members.heading'), __('realm.settings.members.subtitle'))
                    ->schema([
                        // Stack columns cannot be searchable(), so the search box is
                        // enabled on the component; the table source applies the term.
                        Table::lazy(RealmMembersTable::class)->searchable(),
                    ]),
                Card::make(__('realm.settings.members.invitations.heading'), __('realm.settings.members.invitations.subtitle'))
                    ->schema([
                        Table::lazy(RealmInvitationsTable::class)->searchable(),
                    ]),
            ]);
    }
}
