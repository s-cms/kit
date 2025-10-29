<?php

namespace SmartCms\Kit\Services\Block;

use Illuminate\Support\Collection;
use Lorisleiva\Actions\Concerns\AsAction;

class GetBlocks
{
    use AsAction;

    public function handle(string $path): Collection
    {
        $schemaPath = $path;

        if (! file_exists($schemaPath)) {
            return collect();
        }

        $schemas = json_decode(file_get_contents($schemaPath), true);
        if (! is_array($schemas) || ! isset($schemas['schemas'])) {
            return collect();
        }

        return collect($schemas['schemas']);

        // $options = [];
        // foreach ($schemas['schemas'] as $sectionName => $sectionData) {
        //     $options[$sectionName] = $sectionData['title'] . ' - ' . $sectionData['description'];
        // }
        // ...
    }
}
