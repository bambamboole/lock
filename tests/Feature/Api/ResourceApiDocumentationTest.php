<?php

declare(strict_types=1);

use App\Shared\Authorization\Enums\Scope;
use Dedoc\Scramble\Generator;

it('documents resource and scope operations with the scopes the playground must request', function () {
    $document = app(Generator::class)();

    foreach (['/v1/realms/{realm}/resources', '/v1/realms/{realm}/resources/{resource}/scopes'] as $collection) {
        $item = $collection.(str_ends_with($collection, '/scopes') ? '/{scope}' : '/{resource}');

        foreach ([$collection, $item] as $path) {
            expect($document['paths'][$path]['get']['security'] ?? null)
                ->toBe([['oauth2' => [Scope::ResourcesRead->value]]]);
        }

        foreach (['post' => $collection, 'put' => $item, 'delete' => $item] as $method => $path) {
            expect($document['paths'][$path][$method]['security'] ?? null)
                ->toBe([['oauth2' => [Scope::ResourcesWrite->value]]]);
        }

        expect($document['paths'][$collection]['post']['responses'] ?? [])
            ->toHaveKey('201')->not->toHaveKey('200');
    }

    foreach (['CreateResourceData', 'CreateResourceScopeData', 'UpdateResourceData', 'UpdateResourceScopeData'] as $name) {
        expect($document['components']['schemas'][$name]['properties'] ?? [])->not->toBeEmpty();
    }

    expect($document['components']['schemas']['UpdateResourceData']['required'] ?? [])
        ->toBeEmpty()
        ->and($document['components']['schemas']['UpdateResourceScopeData']['required'] ?? [])
        ->toBeEmpty();
});
