<?php

namespace SmartCms\Kit\Services\Block;

use Illuminate\Support\Collection;

class BlockService
{
    public string $path;

    public Collection $blocks;

    public function __construct()
    {
        $this->path = storage_path('app/sections_1.json');
        $this->blocks = GetBlocks::run($this->path);
    }

    public function getBlocksTypes(): array
    {
        return $this->blocks->filter(function ($block) {
            return isset($block['id']);
        })->mapWithKeys(function ($block) {
            return [$block['id'] ?? 0 => $block['title'] . ' - ' . $block['description']];
        })->toArray();
    }

    public function getBlockSchema(?string $id, ?string $language = null): array
    {
        if (! $id) {
            return [];
        }
        $block = $this->blocks->firstWhere('id', $id);
        if (! $block) {
            return [];
        }
        $schema = FilamentSchemaParser::getFieldsForSection($block, $language);

        return $schema;
    }
}
