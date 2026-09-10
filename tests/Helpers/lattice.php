<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Lattice\Support\Testing\ComponentNode;
use RuntimeException;

/**
 * The rendered Lattice component tree of an Inertia page response, rooted at
 * `props.lattice.schema` by default (pass the layout schema path to walk a
 * layout tree instead).
 *
 * @param  TestResponse<Response>  $response
 */
function latticeSchema(TestResponse $response, string $path = 'props.lattice.schema'): ComponentNode
{
    $schema = data_get(AssertableInertia::fromTestResponse($response)->toArray(), $path);

    return ComponentNode::root(is_array($schema) ? $schema : []);
}

/**
 * The `value` of every tab rendered on a Lattice page, in render order.
 *
 * @param  TestResponse<Response>  $response
 * @return array<int, mixed>
 */
function renderedTabKeys(TestResponse $response): array
{
    return collect(latticeSchema($response)
        ->findAll(fn (ComponentNode $node): bool => $node->type() === 'tab'))
        ->map(fn (ComponentNode $node): mixed => $node->prop('value'))
        ->all();
}

/**
 * @return array<int, mixed>
 */
function menuItemHrefs(ComponentNode $menu): array
{
    return collect($menu->findAll(fn (ComponentNode $node): bool => $node->type() === 'menu-item'))
        ->map(fn (ComponentNode $node): mixed => $node->prop('href'))
        ->all();
}

/**
 * The triggers in the trailing action slot of a page header. A header with
 * nothing to offer renders no slot at all, and answers with an empty list.
 *
 * @param  TestResponse<Response>  $response
 * @return array<int, string>
 */
function headerTriggers(TestResponse $response): array
{
    $slot = headerSlot($response);

    return $slot instanceof ComponentNode ? triggerIds($slot) : [];
}

/**
 * The page header's trailing action slot, or null when the header offers
 * nothing and renders no slot at all.
 *
 * @param  TestResponse<Response>  $response
 */
function headerSlot(TestResponse $response): ?ComponentNode
{
    return latticeSchema($response)->find(fn (ComponentNode $node): bool => $node->key() === 'page-header-actions');
}

/**
 * Every trigger inside a node, in render order: an action by its definition id,
 * a link by its key. Both render as a button on their own and as a row inside
 * an action menu.
 *
 * @return array<int, string>
 */
function triggerIds(ComponentNode $node): array
{
    return collect($node->findAll(fn (ComponentNode $item): bool => in_array($item->type(), ['action', 'link'], true)))
        ->map(fn (ComponentNode $item): string => $item->id() ?? $item->key() ?? '')
        ->all();
}

/**
 * The row keys the cells of one table column bind to, in render order — what a
 * rendered cell needs the row payload to carry.
 *
 * @return array<int, string>
 */
function columnBindings(ComponentNode $table, string $columnKey): array
{
    $columns = $table->prop('columns');

    foreach (is_array($columns) ? $columns : [] as $column) {
        if (data_get($column, 'key') !== $columnKey) {
            continue;
        }

        $cells = data_get($column, 'schema');
        $keys = [];

        foreach (is_array($cells) ? $cells : [] as $cell) {
            $bindings = data_get($cell, 'props.dataBindings');

            foreach (is_array($bindings) ? $bindings : [] as $key) {
                if (is_string($key)) {
                    $keys[] = $key;
                }
            }
        }

        return $keys;
    }

    throw new RuntimeException("The table has no column keyed [{$columnKey}].");
}

/**
 * One row of a loaded table response, by its id.
 *
 * @return array<string, mixed>
 */
function tableRow(mixed $rows, string $id): array
{
    $row = collect(is_array($rows) ? $rows : [])->firstWhere('id', $id);

    if (! is_array($row)) {
        throw new RuntimeException("The table returned no row with the id [{$id}].");
    }

    return $row;
}

/**
 * The triggers in a row's overflow menu, empty when the row carries none.
 *
 * @param  array<string, mixed>  $row
 * @return array<int, string>
 */
function rowMenuTriggers(array $row): array
{
    $actions = $row['actions'] ?? [];

    if (! is_array($actions) || $actions === [] || ! is_array($actions[0])) {
        return [];
    }

    return triggerIds(new ComponentNode($actions[0]));
}

/**
 * The overflow menu of every row that carries one.
 *
 * @return array<int, ComponentNode>
 */
function rowMenus(mixed $rows): array
{
    return collect(is_array($rows) ? $rows : [])
        ->map(fn (mixed $row): mixed => is_array($row) ? ($row['actions'][0] ?? null) : null)
        ->filter(fn (mixed $menu): bool => is_array($menu))
        ->map(fn (array $menu): ComponentNode => new ComponentNode($menu))
        ->values()
        ->all();
}

/**
 * @param  array<string, mixed>  $row
 */
function rowClickHref(array $row): ?string
{
    $href = data_get($row, 'rowClick.props.href');

    return is_string($href) ? $href : null;
}
