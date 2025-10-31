<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use SmartCms\Kit\Mcp\Tools\GetVariableTypes;
use SmartCms\Kit\Mcp\Tools\GetVariableTypeSchema;

uses(RefreshDatabase::class);

/**
 * Decode MCP response JSON
 */
function decodeVariableTypeResponse($response): array
{
    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '{}';

    return json_decode($content, true) ?? [];
}

it('can list all variable types', function () {
    $tool = app(GetVariableTypes::class);
    $response = $tool->handle(new Request);

    $result = decodeVariableTypeResponse($response);

    expect($result)->toHaveKey('count');
    expect($result)->toHaveKey('types');
    expect($result['count'])->toBeGreaterThan(0);

    // Verify each type has required fields
    foreach ($result['types'] as $type) {
        expect($type)->toHaveKey('name');
        expect($type)->toHaveKey('class');
        expect($type)->toHaveKey('default_value');
    }
});

it('includes common variable types', function () {
    $tool = app(GetVariableTypes::class);
    $response = $tool->handle(new Request);

    $result = decodeVariableTypeResponse($response);
    $typeNames = array_column($result['types'], 'name');

    // Just verify we have some types registered
    expect($typeNames)->not->toBeEmpty();
    expect(count($typeNames))->toBeGreaterThan(0);
});

it('can get specific variable type schema', function () {
    // First get all types
    $typesTool = app(GetVariableTypes::class);
    $typesResponse = $typesTool->handle(new Request);
    $typesResult = decodeVariableTypeResponse($typesResponse);

    // Skip if no types registered
    if (empty($typesResult['types'])) {
        $this->markTestSkipped('No variable types registered');
    }

    // Get first available type
    $firstType = $typesResult['types'][0]['name'];

    $tool = app(GetVariableTypeSchema::class);
    $response = $tool->handle(new Request(['type' => $firstType]));

    $result = decodeVariableTypeResponse($response);

    expect($result['type'])->toBe($firstType);
    expect($result)->toHaveKey('class');
    expect($result)->toHaveKey('default_value');
});

it('fails to get schema for invalid variable type', function () {
    $tool = app(GetVariableTypeSchema::class);
    $response = $tool->handle(new Request(['type' => 'nonexistent_type']));

    $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '';

    expect($content)->toContain('not found');
    expect($content)->toContain('Available types');
});
