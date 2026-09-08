<?php
declare(strict_types=1);

namespace App\Shared\Ui\Pages;

use Illuminate\Http\Request;
use Lattice\Core\Attributes\AsPage;
use Lattice\Core\Enums\PageWidth;
use Lattice\Http\Page;
use Lattice\Ui\Components\Badge;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Heading;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Justify;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\TextAlign;
use Lattice\Ui\Enums\Variant;
use Lattice\Ui\PageSchema;

#[AsPage(route: '/', name: 'home', width: PageWidth::Large, middleware: ['web'])]
final class WelcomePage extends Page
{
    public function title(): string
    {
        return __('welcome.title');
    }

    public function render(PageSchema $schema, Request $request): PageSchema
    {
        return $schema->schema([
            Stack::make('welcome-hero')
                ->gap(Gap::Large)
                ->align(Align::Center)
                ->schema([
                    Badge::make(__('welcome.badge')),
                    Heading::make(__('welcome.heading')),
                    Text::make(__('welcome.subtitle'))
                        ->align(TextAlign::Center),
                    Stack::make('welcome-actions')
                        ->gap(Gap::Small)
                        ->direction(Orientation::Horizontal)
                        ->justify(Justify::Center)
                        ->schema($this->actions($request->user() !== null)),
                ]),
        ]);
    }

    /**
     * @return array<int, Button>
     */
    private function actions(bool $authenticated): array
    {
        if ($authenticated) {
            return [
                Button::make(__('welcome.actions.dashboard'))
                    ->href(route('dashboard.home', absolute: false)),
            ];
        }

        return [
            Button::make(__('common.action.log-in'))
                ->href(route('login', absolute: false))
                ->variant(Variant::Secondary),
            Button::make(__('welcome.actions.register'))
                ->href(route('identity.register', absolute: false)),
        ];
    }
}
