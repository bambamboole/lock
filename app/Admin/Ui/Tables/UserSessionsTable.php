<?php
declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\EndUserSessionAction;
use App\Auth\Models\User;
use App\Realms\Models\Realm;
use Bambamboole\LaravelOidc\Server\Sessions\Models\OidcSession;
use Bambamboole\LaravelOidc\Server\Sessions\Models\SessionParticipant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Lattice\Actions\Components\Action;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\BadgeColumn;
use Lattice\Table\Columns\NumberColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Ui\Enums\DateTimeStyle;

/**
 * @extends EloquentTableDefinition<OidcSession>
 */
#[AsTable(UserSessionsTable::ID, can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class UserSessionsTable extends EloquentTableDefinition
{
    public const string ID = 'admin.users.sessions';

    private const string ACTIVE = 'active';

    private const string EXPIRED = 'expired';

    private const string REVOKED = 'revoked';

    #[\Override]
    public function authorize(Request $request): bool
    {
        $realm = $this->contextModelOrNull('realm');
        $user = $this->contextModelOrNull('user');

        return $realm instanceof Realm && $user instanceof User && $user->belongsToRealm($realm);
    }

    /**
     * @return Builder<OidcSession>
     */
    public function builder(TableQuery $query): Builder
    {
        $user = $this->user();

        $builder = OidcSession::query()
            ->where('oidc_sessions.realm_id', $user->realm->slug)
            ->where('oidc_sessions.user_id', $user->id)
            ->select('oidc_sessions.*')
            ->selectRaw('oidc_sessions.sid as id')
            ->selectRaw(
                'case when oidc_sessions.revoked_at is not null then ? when oidc_sessions.expires_at <= ? then ? else ? end as status',
                [self::REVOKED, now(), self::EXPIRED, self::ACTIVE],
            )
            ->selectSub(
                SessionParticipant::query()->selectRaw('count(*)')->whereColumn('oidc_session_participants.sid', 'oidc_sessions.sid'),
                'participants_count',
            );

        if ($query->sorts === []) {
            $builder->latest('oidc_sessions.created_at');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('sid')->label(__('admin.users.detail.sessions.columns.sid'))->copyable(),
            BadgeColumn::make('status')
                ->label(__('admin.users.detail.sessions.columns.status'))
                ->options([
                    BadgeColumn::option(__('admin.users.detail.sessions.status.active'), self::ACTIVE),
                    BadgeColumn::option(__('admin.users.detail.sessions.status.expired'), self::EXPIRED),
                    BadgeColumn::option(__('admin.users.detail.sessions.status.revoked'), self::REVOKED),
                ])
                ->colors([self::ACTIVE => 'green', self::EXPIRED => 'gray', self::REVOKED => 'red']),
            NumberColumn::make('participants_count')->label(__('admin.users.detail.sessions.columns.clients')),
            TextColumn::make('created_at')->label(__('admin.users.detail.sessions.columns.started-at'))->dateTime(DateTimeStyle::Short)->sortable(),
            TextColumn::make('expires_at')->label(__('admin.users.detail.sessions.columns.expires-at'))->dateTime(DateTimeStyle::Short)->sortable(),
        ];
    }

    #[\Override]
    public function emptyLabel(): string
    {
        return __('admin.users.detail.sessions.empty');
    }

    public function actions(array $row): array
    {
        if (($row['status'] ?? null) !== self::ACTIVE) {
            return [];
        }

        return [
            Action::use(EndUserSessionAction::class, ['sid' => $row['id']]),
        ];
    }

    private function user(): User
    {
        $realm = $this->contextModel('realm');
        $user = $this->contextModel('user');

        abort_unless($realm instanceof Realm && $user instanceof User && $user->belongsToRealm($realm), 404);

        return $user;
    }
}
