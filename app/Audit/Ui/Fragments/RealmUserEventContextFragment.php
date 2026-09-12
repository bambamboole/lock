<?php
declare(strict_types=1);

namespace App\Audit\Ui\Fragments;

use App\Audit\Models\UserEvent;
use App\Audit\Ui\Concerns\RendersEventContext;
use App\Realms\Models\Realm;
use App\Shared\Authorization\Enums\Scope;
use Lattice\Core\Attributes\AsFragment;
use Lattice\Fragments\FragmentDefinition;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\PageSchema;

#[AsFragment('admin.realm-user-event-context', can: Scope::UserEventsRead)]
final class RealmUserEventContextFragment extends FragmentDefinition
{
    use RendersEventContext;

    public function schema(PageSchema $schema): PageSchema
    {
        $event = $this->userEvent();

        if (! $event instanceof UserEvent) {
            return $schema->schema([Text::make(__('admin.events.detail.empty'))]);
        }

        $lines = $this->detailLines([
            __('admin.events.detail.client') => $event->client_id,
            __('admin.events.detail.session') => $event->sid,
            __('admin.events.detail.ip') => $event->ip,
            __('admin.events.detail.user-agent') => $event->user_agent,
        ], $event->context ?? []);

        if ($lines === []) {
            return $schema->schema([Text::make(__('admin.events.detail.empty'))]);
        }

        return $schema->schema([
            Stack::make('user-event-context-'.$event->getKey())->gap(Gap::Small)->schema($lines),
        ]);
    }

    private function userEvent(): ?UserEvent
    {
        $realm = $this->contextModelOrNull('realm', Realm::class);

        return $realm instanceof Realm ? UserEvent::forRealm($realm)->find($this->contextString('userEvent')) : null;
    }
}
