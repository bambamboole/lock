<?php

declare(strict_types=1);

namespace App\Shared\Ui\Components;

use Lattice\Actions\Components\Action;
use Lattice\Actions\Components\ActionGroup;
use Lattice\Ui\Components\Button;
use Lattice\Ui\Components\Component;
use Lattice\Ui\Components\Link;
use Lattice\Ui\Enums\Emphasis;

/**
 * The one place a console action gets its appearance: outlined buttons for the
 * few actions an operator reaches for, a single overflow menu for the rest.
 *
 * An `Action` component ships whatever emphasis its definition set, and a
 * definition that sets none renders as bare text — so the emphasis is applied
 * here rather than left to each call site. Menu items need no emphasis at all:
 * the client styles everything inside an action group as a menu row.
 */
final class ActionBar
{
    /**
     * @param  array<int, Action|Button>  $primary
     * @param  array<int, Action|Link>  $overflow
     * @return array<int, Component>
     */
    public static function make(array $primary = [], array $overflow = [], string $key = 'page'): array
    {
        $buttons = array_map(self::button(...), self::renderable($primary));

        $menu = self::menu($key.'-overflow-actions', $overflow);

        return $menu instanceof ActionGroup ? [...$buttons, $menu] : $buttons;
    }

    /**
     * A single action rendered as a button rather than a menu row — the same
     * appearance {@see self::make()} gives the primary actions of a header.
     */
    public static function button(Action|Button $action): Action|Button
    {
        return $action->emphasis(Emphasis::Outline);
    }

    /**
     * The `⋯` menu, or null when nothing inside it may be rendered — an action
     * group with no items still draws its trigger.
     *
     * @param  array<int, Action|Link>  $actions
     */
    public static function menu(string $id, array $actions): ?ActionGroup
    {
        $actions = self::renderable($actions);

        if ($actions === []) {
            return null;
        }

        return ActionGroup::make($id)->label(__('common.action.more'))->actions($actions);
    }

    /**
     * @template T of Component
     *
     * @param  array<int, T>  $components
     * @return array<int, T>
     */
    private static function renderable(array $components): array
    {
        return array_values(array_filter($components, static fn (Component $component): bool => $component->shouldRender()));
    }
}
