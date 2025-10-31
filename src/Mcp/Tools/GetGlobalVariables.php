<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;

#[IsReadOnly]
class GetGlobalVariables extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_global_variables';

    /**
     * The tool's description.
     */
    protected string $description = 'Get all globally shared variables available to all templates';

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        return Response::json([
            'host' => [
                'title' => hostname(),
                'type' => 'link',
                'is_external' => false,
                'url' => host(),
            ],
            'hostname' => hostname(),
            'company_name' => company_name(),
            'logo' => logo(),
            'default_image' => no_image(),
            'locale' => main_lang(),
            'locales' => language_routes(),
        ]);
    }
}
