<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Lattice\Support\Testing\ComponentNode;
use RuntimeException;

/**
 * @param  TestResponse<Response>  $response
 */
function latticeSchema(TestResponse $response, string $path = 'props.lattice.schema'): ComponentNode
{
    $schema = data_get(AssertableInertia::fromTestResponse($response)->toArray(), $path);

    if (! is_array($schema)) {
        throw new RuntimeException("The response renders no Lattice schema at [{$path}].");
    }

    return ComponentNode::root($schema);
}

/**
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
 * A header with nothing to offer renders no slot at all.
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
 * @param  TestResponse<Response>  $response
 */
function headerSlot(TestResponse $response): ?ComponentNode
{
    return latticeSchema($response)->find(fn (ComponentNode $node): bool => $node->key() === 'page-header-actions');
}

/**
 * @return array<int, string>
 */
function triggerIds(ComponentNode $node): array
{
    return collect($node->findAll(fn (ComponentNode $item): bool => in_array($item->type(), ['action', 'link'], true)))
        ->map(fn (ComponentNode $item): string => $item->id() ?? $item->key() ?? '')
        ->all();
}

/**
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
