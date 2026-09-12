<?php
declare(strict_types=1);

namespace App\Auth\Ui\Pages;

use App\Auth\Ui\Forms\CreateUserForm;
use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/users/create', name: 'admin.realms.users.create', can: ApiScope::UsersWrite)]
final class CreateUserPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.users.create.heading');
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $schema->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.users'), route('admin.realms.users', ['realm' => $realm->slug], false)),
            Breadcrumb::make(__('admin.users.create.heading'), route('admin.realms.users.create', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-create-user-page')->gap(Gap::Large)->width(Width::Medium)->schema([
                PageHeader::make(__('admin.users.create.heading'), __('admin.users.create.description', ['realm' => $realm->name])),
                Card::make(__('admin.users.detail.profile.heading'))->schema([
                    Form::use(CreateUserForm::class),
                ]),
            ]),
        ]);
    }
}
