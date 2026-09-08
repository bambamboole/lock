<?php
declare(strict_types=1);

namespace App\Shared\Ui\Fragments;

use App\Realms\Models\Activity;
use App\Shared\Ui\Concerns\RendersActivityChanges;
use Lattice\Fragments\FragmentDefinition;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

abstract class ActivityChangesFragment extends FragmentDefinition
{
    use RendersActivityChanges;

    abstract protected function activity(): ?Activity;

    abstract protected function emptyText(): string;

    public function schema(PageSchema $schema): PageSchema
    {
        $activity = $this->activity();
        $properties = $activity?->properties?->all() ?? [];

        if ($activity === null || $properties === []) {
            return $schema->schema([
                Text::make($this->emptyText()),
            ]);
        }

        return $schema->schema([
            Stack::make('activity-changes-'.$activity->getKey())->gap(Gap::Small)->schema($this->changeLines($properties)),
        ]);
    }
}
