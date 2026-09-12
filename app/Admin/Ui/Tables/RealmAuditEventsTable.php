<?php

declare(strict_types=1);

namespace App\Admin\Ui\Tables;

use App\Admin\Ui\Fragments\AuditEventContextFragment;
use App\Realms\Models\Realm;
use App\Shared\Audit\Enums\AuditEventCategory;
use App\Shared\Audit\Enums\AuditEventType;
use App\Shared\Audit\Models\AuditEvent;
use App\Shared\Authorization\Enums\Scope;
use Illuminate\Database\Eloquent\Builder;
use Lattice\Core\Enums\ColorName;
use Lattice\Fragments\Components\Fragment;
use Lattice\Table\Attributes\AsTable;
use Lattice\Table\Columns\BadgeColumn;
use Lattice\Table\Columns\TextColumn;
use Lattice\Table\Filters\SelectFilter;
use Lattice\Table\Filters\TernaryFilter;
use Lattice\Table\Sources\Eloquent\EloquentTableDefinition;
use Lattice\Table\TableQuery;
use Lattice\Ui\Enums\ColumnWidth;

/**
 * @extends EloquentTableDefinition<AuditEvent>
 */
#[AsTable('admin.realm-events', can: Scope::ActivityRead)]
final class RealmAuditEventsTable extends EloquentTableDefinition
{
    /**
     * @return Builder<AuditEvent>
     */
    public function builder(TableQuery $query): Builder
    {
        $realm = $this->contextModel('realm', Realm::class);

        $builder = AuditEvent::forRealm($realm)->with('user');

        if ($query->sorts === []) {
            $builder->latest('occurred_at');
        }

        return $builder;
    }

    public function columns(): array
    {
        return [
            TextColumn::make('occurred_at')->label(__('admin.events.columns.when'))->dateTime()->sortable(),
            TextColumn::make('actor_name')->label(__('admin.events.columns.actor')),
            BadgeColumn::make('type')
                ->label(__('admin.events.columns.type'))
                ->width(ColumnWidth::Lg)
                ->colors($this->typeColors()),
            TextColumn::make('type_label')->label(__('admin.events.columns.description')),
            TextColumn::make('ip')->label(__('admin.events.columns.ip')),
        ];
    }

    /**
     * A failure has to stand out while scrolling, so everything else stays
     * quiet.
     *
     * @return array<string, ColorName>
     */
    private function typeColors(): array
    {
        $colors = [];

        foreach (AuditEventType::cases() as $type) {
            $colors[$type->value] = match (true) {
                $type->isFailure() => ColorName::Red,
                $type->category() === AuditEventCategory::Admin => ColorName::Blue,
                default => ColorName::Gray,
            };
        }

        return $colors;
    }

    public function filters(): array
    {
        return [
            SelectFilter::make('category')->label(__('admin.events.filters.category'))->options(AuditEventCategory::options()),
            SelectFilter::make('type')->label(__('admin.events.filters.type'))->options(AuditEventType::options())->multiple(),
            TernaryFilter::make('failure')->label(__('admin.events.filters.failure')),
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     */
    #[\Override]
    public function rowDetail(array $row): Fragment
    {
        return Fragment::lazy(AuditEventContextFragment::class, ['auditEvent' => $row['id']]);
    }

    public function emptyLabel(): string
    {
        return __('admin.events.empty');
    }
}
