<?php

declare(strict_types=1);

namespace App\Admin\Ui\Fragments;

use App\Realms\Models\Realm;
use App\Shared\Api\Enums\ApiScope;
use App\Shared\Audit\Models\AuditEvent;
use App\Shared\Ui\Concerns\RendersActivityChanges;
use Lattice\Core\Attributes\AsFragment;
use Lattice\Fragments\FragmentDefinition;
use Lattice\Ui\Components\Stack;
use Lattice\Ui\Components\Text;
use Lattice\Ui\Enums\Gap;
use Lattice\Ui\Enums\Size;
use Lattice\Ui\PageSchema;

#[AsFragment('admin.audit-event-context', can: ApiScope::ActivityRead)]
final class AuditEventContextFragment extends FragmentDefinition
{
    use RendersActivityChanges;

    public function schema(PageSchema $schema): PageSchema
    {
        $event = $this->auditEvent();
        $lines = $event instanceof AuditEvent ? $this->detailLines($event) : [];

        if ($event === null || $lines === []) {
            return $schema->schema([Text::make(__('admin.events.detail.empty'))]);
        }

        return $schema->schema([
            Stack::make('audit-event-context-'.$event->getKey())->gap(Gap::Small)->schema($lines),
        ]);
    }

    /**
     * @return array<int, Text>
     */
    private function detailLines(AuditEvent $event): array
    {
        $labelled = [
            __('admin.events.detail.client') => $event->client_id,
            __('admin.events.detail.session') => $event->sid,
            __('admin.events.detail.ip') => $event->ip,
            __('admin.events.detail.user-agent') => $event->user_agent,
        ];

        $lines = [];

        foreach ($labelled as $label => $value) {
            if ($value !== null && $value !== '') {
                $lines[] = Text::make($label.': '.$value)->size(Size::Sm);
            }
        }

        return [...$lines, ...$this->changeLines($event->context ?? [])];
    }

    private function auditEvent(): ?AuditEvent
    {
        $realm = $this->contextModelOrNull('realm', Realm::class);

        if (! $realm instanceof Realm) {
            return null;
        }

        return AuditEvent::forRealm($realm)->find($this->contextString('auditEvent'));
    }
}
