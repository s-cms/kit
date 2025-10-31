<?php

// use Illuminate\Foundation\Testing\RefreshDatabase;
// use Laravel\Mcp\Request;
// use SmartCms\Kit\Mcp\Tools\CreateBlockInstance;
// use SmartCms\Kit\Mcp\Tools\GetBlock;
// use SmartCms\Kit\Mcp\Tools\GetBlockSchema;
// use SmartCms\Kit\Mcp\Tools\GetBlockSchemas;
// use SmartCms\Kit\Mcp\Tools\UpdateBlockData;
// use SmartCms\Kit\Models\Block;
// use SmartCms\Kit\Services\Block\BlockService;

// uses(RefreshDatabase::class);

// /**
//  * Decode MCP response JSON
//  */
// function decodeBlockResponse($response): array
// {
//     $content = $response->content[0]['text'] ?? $response->content()->__toString() ?? '{}';

//     return json_decode($content, true) ?? [];
// }

// it('can list block schemas', function () {
//     $tool = app(GetBlockSchemas::class);
//     $response = $tool->handle(new Request());

//     $result = decodeBlockResponse($response);

//     expect($result)->toHaveKey('count');
//     expect($result)->toHaveKey('schemas');
//     expect($result['count'])->toBeGreaterThan(0);
// });

// it('can get specific block schema', function () {
//     $blockService = app(BlockService::class);
//     $firstSchema = $blockService->blocks->first();

//     $tool = app(GetBlockSchema::class);
//     $response = $tool->handle(new Request([
//         'type' => $firstSchema['id'],
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result['id'])->toBe($firstSchema['id']);
//     expect($result)->toHaveKey('properties');
// });

// it('fails to get block schema for invalid type', function () {
//     $tool = app(GetBlockSchema::class);
//     $response = $tool->handle(new Request([
//         'type' => 'NonExistentBlockType',
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result)->toHaveKey('error');
//     expect($result)->toHaveKey('available_types');
// });

// it('can get block by id', function () {
//     $block = Block::factory()->create([
//         'type' => 'TestBlock',
//         'title' => 'Test Block',
//     ]);

//     $tool = app(GetBlock::class);
//     $response = $tool->handle(new Request(['id' => $block->id]));

//     $result = decodeBlockResponse($response);

//     expect($result['id'])->toBe($block->id);
//     expect($result['type'])->toBe('TestBlock');
//     expect($result['title'])->toBe('Test Block');
//     expect($result)->toHaveKey('schema');
//     expect($result)->toHaveKey('data');
// });

// it('can get block with specific language', function () {
//     $block = Block::factory()->create([
//         'data' => [
//             'en' => ['title' => 'English Title'],
//             'uk' => ['title' => 'Ukrainian Title'],
//         ],
//     ]);

//     $tool = app(GetBlock::class);
//     $response = $tool->handle(new Request([
//         'id' => $block->id,
//         'language' => 'uk',
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result['language'])->toBe('uk');
//     expect($result['data'])->toBe(['title' => 'Ukrainian Title']);
// });

// it('can create block instance', function () {
//     $blockService = app(BlockService::class);
//     $schema = $blockService->blocks->first();

//     $tool = app(CreateBlockInstance::class);
//     $response = $tool->handle(new Request([
//         'type' => $schema['id'],
//         'title' => 'New Block Instance',
//         'status' => true,
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result['success'])->toBeTrue();
//     expect($result['block']['title'])->toBe('New Block Instance');
//     expect($result['block']['type'])->toBe($schema['id']);

//     assertDatabaseHas('blocks', [
//         'type' => $schema['id'],
//         'title' => 'New Block Instance',
//     ]);
// });

// it('fails to create block for invalid type', function () {
//     $tool = app(CreateBlockInstance::class);
//     $response = $tool->handle(new Request([
//         'type' => 'InvalidBlockType',
//         'title' => 'Test',
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result['success'])->toBeFalse();
//     expect($result)->toHaveKey('error');
//     expect($result)->toHaveKey('available_types');
// });

// it('can update block data with merge', function () {
//     $block = Block::factory()->create([
//         'schema' => [
//             'type' => 'object',
//             'properties' => [
//                 'title' => ['type' => 'string'],
//                 'subtitle' => ['type' => 'string'],
//             ],
//         ],
//         'data' => [
//             'en' => [
//                 'title' => 'Original Title',
//                 'subtitle' => 'Original Subtitle',
//             ],
//         ],
//     ]);

//     $tool = app(UpdateBlockData::class);
//     $response = $tool->handle(new Request([
//         'id' => $block->id,
//         'data' => [
//             'title' => 'Updated Title',
//             // Not updating subtitle
//         ],
//         'language' => 'en',
//         'merge' => true,
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result['success'])->toBeTrue();

//     // Verify merge preserved subtitle
//     $block->refresh();
//     $data = $block->getTranslation('data', 'en');
//     expect($data['title'])->toBe('Updated Title');
//     expect($data['subtitle'])->toBe('Original Subtitle');
// });

// it('can update block data with replace', function () {
//     $block = Block::factory()->create([
//         'schema' => [
//             'type' => 'object',
//             'properties' => [
//                 'title' => ['type' => 'string', 'default' => ''],
//                 'subtitle' => ['type' => 'string', 'default' => ''],
//             ],
//         ],
//         'data' => [
//             'en' => [
//                 'title' => 'Original Title',
//                 'subtitle' => 'Original Subtitle',
//             ],
//         ],
//     ]);

//     $tool = app(UpdateBlockData::class);
//     $response = $tool->handle(new Request([
//         'id' => $block->id,
//         'data' => [
//             'title' => 'New Title',
//         ],
//         'language' => 'en',
//         'merge' => false,
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result['success'])->toBeTrue();

//     // Verify replace mode reset subtitle to default
//     $block->refresh();
//     $data = $block->getTranslation('data', 'en');
//     expect($data['title'])->toBe('New Title');
// });

// it('fails to update block data for nonexistent block', function () {
//     $tool = app(UpdateBlockData::class);
//     $response = $tool->handle(new Request([
//         'id' => 999999,
//         'data' => ['title' => 'Test'],
//     ]));

//     $result = decodeBlockResponse($response);

//     expect($result['success'])->toBeFalse();
//     expect($result)->toHaveKey('error');
// });
