<?php
declare(strict_types=1);

namespace App\Realms\Ui\Pages;

use App\Realms\Ui\Forms\CreateRealmForm;
use App\Shared\Authorization\Enums\Scope;
use App\Shared\Ui\Pages\AdminPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Form\Components\Form;
use Lattice\Ui\Components\Card;
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
        $schema->breadcrumbs([
            Breadcrumb::make(__('navigation.realms'), route('admin.realms', [], false)),
            Breadcrumb::make(__('admin.realms.create.heading'), route('admin.realms.create', [], false)),
        ]);

        return $schema->schema([
            $this->stack(
                key: 'admin-create-realm-page',
                heading: __('admin.realms.create.heading'),
                description: __('admin.realms.create.description'),
                schema: [
                    Card::make(__('admin.realms.sections.general'))->schema([
                        Form::use(CreateRealmForm::class),
                    ]),
                ],
                width: Width::Medium,
            ),
        ]);
    }
}
