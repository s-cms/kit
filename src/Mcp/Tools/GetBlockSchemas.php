<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SmartCms\Kit\Services\Block\BlockService;

#[IsReadOnly]
class GetBlockSchemas extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_block_schemas';

    /**
     * The tool's description.
     */
    protected string $description = 'Get all available block/section schemas from BlockService';

    public function __construct(
        protected BlockService $blockService
    ) {}

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        return Response::json($this->blockService->blocks);
    }
}
