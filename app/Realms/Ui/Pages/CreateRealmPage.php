<?php
declare(strict_types=1);

namespace App\Realms\Ui\Pages;

use App\Realms\Ui\Forms\CreateRealmForm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/admin/realms/create', name: 'admin.realms.create', can: Scope::RealmsWrite)]
final class CreateRealmPage extends AdminPage
{
    public function title(): string
    {
        return __('admin.realms.create.heading');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('admin-create-realm-page')->gap(Gap::Large)->width(Width::Medium)->schema([
                PageHeader::make(__('admin.realms.create.heading'), __('admin.realms.create.description')),
                Card::make(__('admin.realms.sections.general'))->schema([
                    Form::use(CreateRealmForm::class),
                ]),
            ]),
        ]);
    }
}
