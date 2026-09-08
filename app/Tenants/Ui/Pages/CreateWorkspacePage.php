<?php

declare(strict_types=1);

namespace App\Tenants\Ui\Pages;

use App\Tenants\Ui\Forms\CreateTenantForm;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Enums\ColorName;
use Lattice\Core\Enums\PageLayout;
use Lattice\Form\Components\Form;
use Lattice\Http\Page;
use Lattice\Ui\Components\Heading;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\TextAlign;
use Lattice\Ui\PageSchema;

#[AsPage(route: 'onboarding/workspace', name: 'tenants.onboarding', layout: PageLayout::Auth, middleware: ['auth', 'verified'])]
final class CreateWorkspacePage extends Page
{
    public function title(): string
    {
        return __('tenant.onboarding.title');
    }

    public function render(PageSchema $schema): PageSchema
    {
        return $schema->schema([
            Stack::make('workspace-onboarding')
                ->gap(Gap::Large)
                ->schema([
                    Stack::make('workspace-onboarding-heading')
                        ->gap(Gap::Small)
                        ->align(Align::Center)
                        ->schema([
                            Heading::make(__('tenant.onboarding.heading'), 2),
                            Text::make(__('tenant.onboarding.subtitle'))
                                ->color(ColorName::Muted)
                                ->align(TextAlign::Center),
                        ]),
                    Form::use(CreateTenantForm::class),
                ]),
        ]);
    }
}
