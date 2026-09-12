<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Forms\CreateRealmForm;
use App\Shared\Ui\Components\PageHeader;
use Lattice\Core\Attributes\AsPage;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/create', name: 'admin.realms.create', can: [AdminPermission::Access, AdminPermission::ManageRealms])]
final class CreateRealmPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.realms.create.heading');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('admin-create-realm-page')->gap(Gap::Large)->width(Width::Large)->schema([
                PageHeader::make(__('admin.realms.create.heading'), __('admin.realms.create.description')),
                Card::make(__('admin.realms.sections.general'))->schema([
                    Form::use(CreateRealmForm::class),
                ]),
            ]),
        ]);
    }
}
