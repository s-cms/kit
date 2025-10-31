<?php

namespace SmartCms\Kit\Mcp\Tools;

use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SmartCms\TemplateBuilder\Support\VariableTypeRegistry;

#[IsReadOnly]
class GetVariableTypes extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_variable_types';

    /**
     * The tool's description.
     */
    protected string $description = 'List all registered variable types available in SmartCms';

    public function __construct(
        protected VariableTypeRegistry $registry
    ) {}

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $types = $this->registry->all();

        $result = [];
        foreach ($types as $name => $typeClass) {
            $instance = is_string($typeClass) ? app($typeClass) : $typeClass;
            $result[] = [
                'name' => $name,
                'class' => is_string($typeClass) ? $typeClass : get_class($typeClass),
                'default_value' => $instance->getDefaultValue(),
            ];
        }

        return Response::text(json_encode([
            'count' => count($result),
            'types' => $result,
        ], JSON_PRETTY_PRINT));
    }
}
