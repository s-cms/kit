<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetLanguages extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_languages';

    /**
     * The tool's description.
     */
    protected string $description = 'Get all configured languages for the CMS';

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $languages = app('lang')->adminLanguages();
        $frontendLanguages = app('lang')->frontLanguages();

        $result = [
            'main' => main_lang(),
            'frontend' => $frontendLanguages->pluck('slug'),
            'admin' => $languages->pluck('slug'),
        ];

        return Response::json($result);
    }
}
