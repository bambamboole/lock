<?php
declare(strict_types=1);

namespace App\Auth\Ui\Pages;

use App\Auth\Ui\Forms\CreateUserForm;
use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/{realm}/users/create', name: 'admin.realms.users.create', can: Scope::UsersWrite)]
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
            $this->stack(
                key: 'admin-create-user-page',
                heading: __('admin.users.create.heading'),
                description: __('admin.users.create.description', ['realm' => $realm->name]),
                schema: [
                    Card::make(__('admin.users.detail.profile.heading'))->schema([
                        Form::use(CreateUserForm::class),
                    ]),
                ],
                width: Width::Medium,
            ),
        ]);
    }
}
