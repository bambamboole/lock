<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Ui\Forms\CreateClientForm;
use App\Realms\Models\Realm;
use App\Shared\Admin\Enums\AdminPermission;
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

#[AsPage(route: '/admin/realms/{realm}/clients/create', name: 'admin.realms.clients.create', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class CreateClientPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.clients.create.heading');
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $schema->breadcrumbs($this->realmBreadcrumbs(
            $realm,
            Breadcrumb::make(__('navigation.clients'), route('admin.realms.clients', ['realm' => $realm->slug], false)),
            Breadcrumb::make(__('admin.clients.create.heading'), route('admin.realms.clients.create', ['realm' => $realm->slug], false)),
        ));

        return $schema->schema([
            Stack::make('admin-create-client-page')->gap(Gap::Large)->width(Width::Medium)->schema([
                PageHeader::make(__('admin.clients.create.heading'), __('admin.clients.create.description', ['realm' => $realm->name])),
                Card::make(__('admin.clients.sections.general'))->schema([
                    Form::use(CreateClientForm::class),
                ]),
            ]),
        ]);
    }
}
