<?php
declare(strict_types=1);

namespace App\Shared\Ui\Tables;

use App\Shared\Audit\Models\Activity;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Columns\BadgeColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;

/**
 * @extends EloquentTableDefinition<Activity>
 */
abstract class ActivityTable extends EloquentTableDefinition
{
    /**
     * @return Builder<Activity>
     */
    abstract protected function scopedActivity(): Builder;

    abstract protected function langPrefix(): string;

    /**
     * @param  array<string, mixed>  $row
     */
    abstract protected function changesFragment(array $row): Fragment;

    /**
     * The description column is not searchable: it stores a translation key.
     *
     * @return array<string, string>
     */
    abstract protected function eventOptions(): array;

    public function columns(): array
    {
        $prefix = $this->langPrefix();

        return [
            TextColumn::make('created_at')->label(__("{$prefix}.columns.when"))->dateTime()->sortable(),
            TextColumn::make('causer_name')->label(__("{$prefix}.columns.actor")),
            BadgeColumn::make('event')->label(__("{$prefix}.columns.event"))->filterOptions($this->eventOptions()),
            TextColumn::make('description')->label(__("{$prefix}.columns.description")),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    public function rowDetail(array $row): ?Fragment
    {
        if (($row['properties'] ?? []) === []) {
            return null;
        }

        return $this->changesFragment($row);
    }

    public function emptyLabel(): string
    {
        return __($this->langPrefix().'.empty');
    }

    /**
     * @return Builder<Activity>
     */
    public function builder(TableQuery $query): Builder
    {
        $builder = $this->scopedActivity()->with('causer');

        if ($query->sorts === []) {
            $builder->latest('id');
        }

        return $builder;
    }
}
