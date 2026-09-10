<?php

declare(strict_types=1);

namespace App\Realms\Ui\Pages;

use App\Realms\Models\Realm;
use App\Shared\Concerns\ResolvesCurrentUser;
use App\Shared\Ui\Components\PageHeader;
use App\Shared\Ui\Pages\RealmPage;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Breadcrumb;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Tabs;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Width;
use Lattice\Ui\PageSchema;
use Lattice\Ui\Slot;

#[AsPage(route: '/console/{current_realm}/settings', name: 'realm.settings.edit')]
final class RealmSettingsPage extends RealmPage
{
    use ResolvesCurrentUser;

    private ?Realm $realm = null;

    public function title(): ?string
    {
        return $this->realm?->name;
    }

    /**
     * @return array<int, Breadcrumb>
     */
    public function breadcrumbs(): array
    {
        if (! $this->realm instanceof Realm) {
            return [];
        }

        return [Breadcrumb::make(
            $this->realm->name,
            route('realm.settings.edit', absolute: false),
        )];
    }

    public function render(PageSchema $schema, Realm $current_realm): PageSchema
    {
        $this->realm = $current_realm;

        return $schema->schema([
            Stack::make('realm-settings-page')
                ->gap(Gap::Large)
                ->width(Width::Large)
                ->schema([
                    PageHeader::make($current_realm->name, __('realm.settings.subtitle')),
                    Tabs::make('realm-settings-tabs')
                        ->orientation(Orientation::Vertical)
                        ->defaultValue('general')
                        ->schema([
                            Slot::make('realm.settings.tabs')->context(['realm' => $current_realm]),
                        ]),
                ]),
        ]);
    }
}
