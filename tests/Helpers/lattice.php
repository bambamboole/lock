<?php

declare(strict_types=1);

namespace Tests\Helpers;

use Illuminate\Http\Response;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Lattice\Support\Testing\ComponentNode;

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
