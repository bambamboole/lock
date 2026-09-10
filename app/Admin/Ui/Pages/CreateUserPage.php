<?php
declare(strict_types=1);

namespace App\Admin\Ui\Pages;

use App\Admin\Enums\AdminPermission;
use App\Admin\Ui\Forms\CreateUserForm;
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

#[AsPage(route: '/admin/realms/{realm}/users/create', name: 'admin.realms.users.create', can: [AdminPermission::Access, AdminPermission::ManageUsers])]
final class CreateUserPage extends AdminPage
{
    private ?Realm $realm = null;

    public function title(): string
    {
        return __('admin.users.create.heading');
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
            Breadcrumb::make(__('navigation.users'), route('admin.realms.users', ['realm' => $this->realm->slug], false)),
            Breadcrumb::make(__('admin.users.create.heading'), route('admin.realms.users.create', ['realm' => $this->realm->slug], false)),
        ];
    }

    public function render(PageSchema $schema, Realm $realm): PageSchema
    {
        $this->realm = $realm;

        return $schema->schema([
            Stack::make('admin-create-user-page')->gap(Gap::Large)->width(Width::Medium)->schema([
                PageHeader::make(__('admin.users.create.heading'), __('admin.users.create.description', ['realm' => $realm->name])),
                Card::make(__('admin.users.detail.profile.heading'))->schema([
                    Form::use(CreateUserForm::class, ['realm' => $realm->slug]),
                ]),
            ]),
        ]);
    }
}
