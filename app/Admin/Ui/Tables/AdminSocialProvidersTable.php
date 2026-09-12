<?php
declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Actions\DeleteSocialProviderAction;
use App\Admin\Ui\Actions\UpdateSocialProviderAction;
use App\Realms\Models\Realm;
use App\Realms\Models\RealmSocialProvider;
use App\Shared\Realms\Enums\SocialProviderDriver;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Actions\Components\Action;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\BadgeColumn;
use Lattice\Table\Columns\BooleanColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<RealmSocialProvider>
 */
#[AsTable(self::ID, can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class AdminSocialProvidersTable extends EloquentTableDefinition
{
    public const string ID = 'admin.social-providers';

    /**
     * @return Builder<RealmSocialProvider>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm', Realm::class);

        $builder = $realm->socialProviders()->getQuery()->select('realm_social_providers.*');

        if ($query->sorts === []) {
            $builder->orderBy('key');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('key')->label(__('admin.social-providers.columns.key'))->searchable()->sortable()->copyable(),
            BadgeColumn::make('driver')
                ->label(__('admin.social-providers.columns.driver'))
                ->options($this->driverLabels())
                ->colors([
                    SocialProviderDriver::Google->value => 'blue',
                    SocialProviderDriver::Apple->value => 'gray',
                    SocialProviderDriver::GitHub->value => 'purple',
                    SocialProviderDriver::Oidc->value => 'green',
                ]),
            BooleanColumn::make('enabled')->label(__('admin.social-providers.columns.enabled'))->sortable(),
        ];
    }

    public function actions(array $row): array
    {
        $context = ['socialProvider' => $row['id']];

        return [
            Action::use(UpdateSocialProviderAction::class, $context),
            Action::use(DeleteSocialProviderAction::class, $context),
        ];
    }

    /**
     * @return array<string, string>
     */
    private function driverLabels(): array
    {
        $labels = [];

        foreach (SocialProviderDriver::cases() as $driver) {
            $labels[$driver->value] = __('admin.social-providers.drivers.'.$driver->value);
        }

        return $labels;
    }
}
