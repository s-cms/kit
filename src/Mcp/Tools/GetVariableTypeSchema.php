<?php

namespace SmartCms\Kit\Mcp\Tools;

use Illuminate\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\Annotations\IsReadOnly;
use SmartCms\TemplateBuilder\Support\VariableTypeRegistry;

#[IsReadOnly]
class GetVariableTypeSchema extends Tool
{
    /**
     * The tool's name.
     */
    protected string $name = 'get_variable_type_schema';

    /**
     * The tool's description.
     */
    protected string $description = 'Get detailed schema information for a specific variable type';

    public function __construct(
        protected VariableTypeRegistry $registry
    ) {}

    /**
     * The tool's input schema.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()
                ->description('The variable type name (e.g., "string", "menu", "image")')
                ->required(),
        ];
    }

    /**
     * Handle the tool execution.
     */
    public function handle(Request $request): Response
    {
        $type = $request->string('type');
        $typeInstance = $this->registry->get($type);

        if (! $typeInstance) {
            $availableTypes = array_keys($this->registry->all());
            return Response::text(
                "Variable type '{$type}' not found. Available types: " . implode(', ', $availableTypes)
            );
        }

        $instance = is_string($typeInstance) ? app($typeInstance) : $typeInstance;

        return Response::json([
            'type' => $type,
            'class' => is_string($typeInstance) ? $typeInstance : get_class($typeInstance),
            'default_value' => $instance->getDefaultValue(),
        ]);
    }
}
