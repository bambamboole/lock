<?php
declare(strict_types=1);

namespace App\Shared\Ui\Components;

use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\Heading;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Align;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Orientation;
use Lattice\Ui\Enums\Side;
use Lattice\Ui\Enums\Width;

/**
 * The title block every page opens with: an h1, an optional lead paragraph, and
 * actions pushed to the trailing edge. A factory rather than a wire component —
 * it composes existing components and adds nothing the client has to know about.
 */
final class PageHeader
{
    /**
     * @param  array<int, Component>  $actions
     */
    public static function make(string $title, ?string $description = null, array $actions = []): Stack
    {
        $copy = [Heading::make($title, 1)];

        if ($description !== null) {
            $copy[] = Text::make($description);
        }

        $schema = [Stack::make('page-header-copy')->width(Width::Fill)->gap(Gap::Small)->schema($copy)];

        if ($actions !== []) {
            $schema[] = Stack::make('page-header-actions')
                ->float(Side::End)
                ->width(Width::Auto)
                ->direction(Orientation::Horizontal)
                ->align(Align::Center)
                ->gap(Gap::Small)
                ->schema($actions);
        }

        return Stack::make('page-header')
            ->direction(Orientation::Horizontal)
            ->align(Align::Start)
            ->schema($schema);
    }
}
