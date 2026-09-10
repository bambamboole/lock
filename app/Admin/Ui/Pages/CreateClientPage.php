<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Forms\CreateClientForm;
use App\Realms\Models\Realm;
use App\Shared\Ui\Components\PageHeader;
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
    private ?Realm $realm = null;

    public function title(): string
    {
        return __('admin.clients.create.heading');
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->realm instanceof Realm) {
            return [];
        }

        return [
            Breadcrumb::make($this->realm->name, route('admin.realms.users', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make(__('navigation.clients'), route('admin.realms.clients', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make(__('admin.clients.create.heading'), route('admin.realms.clients.create', ['realm' => $this->realm->slug], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-create-client-page')->gap(Gap::Large)->width(Width::Medium)->schema([
                PageHeader::make(__('admin.clients.create.heading'), __('admin.clients.create.description', ['realm' => $realm->name])),
                Card::make(__('admin.clients.sections.general'))->schema([
                    Form::use(CreateClientForm::class, ['realm' => $realm->slug]),
                ]),
            ]),
        ]);
    }
}
