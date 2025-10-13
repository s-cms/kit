<?php

namespace SmartCms\Kit\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeAugmentationCommand extends Command
{
    protected $signature = 'make:augmentation {name : The name of the augmentation}
                            {--model=page : The model to augment (default: page)}
                            {--form : Include ModifiesFormSchema trait}
                            {--columns : Include ModifiesTableColumns trait}
                            {--filters : Include ModifiesTableFilters trait}
                            {--record-actions : Include ModifiesRecordActions trait}
                            {--header-actions : Include ModifiesHeaderActions trait}
                            {--toolbar-actions : Include ModifiesToolbarActions trait}
                            {--bulk-actions : Include ModifiesBulkActions trait}
                            {--casts : Include ModifiesModelCasts trait}
                            {--relations : Include ModifiesModelRelations trait}
                            {--relation-managers : Include ModifiesRelationManagers trait}
                            {--all : Include all traits}
                            {--force : Overwrite existing augmentation}';

    protected $description = 'Create a new augmentation class';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $className = Str::studly($name);

        if (! Str::endsWith($className, 'Augmentation')) {
            $className .= 'Augmentation';
        }

        $path = $this->getPath($className);

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->components->error("Augmentation [{$className}] already exists!");

            return self::FAILURE;
        }

        $this->makeDirectory($path);

        $stub = $this->buildClass($className);

        $this->files->put($path, $stub);

        $this->components->info("Augmentation [{$className}] created successfully.");

        // Show registration instructions
        $this->newLine();
        $this->components->info('Next steps:');
        $this->line('  1. Register in config/augmentations.php:');
        $this->line("     '{$this->option('model')}' => [");
        $this->line("         \\App\\Augmentations\\{$className}::class,");
        $this->line('     ],');
        $this->newLine();
        $this->line("  2. Implement the methods in {$path}");

        return self::SUCCESS;
    }

    protected function getPath(string $className): string
    {
        return app_path("Augmentations/{$className}.php");
    }

    protected function makeDirectory(string $path): void
    {
        if (! $this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }
    }

    protected function buildClass(string $className): string
    {
        $stub = $this->files->get($this->getStub());

        return $this->replaceNamespace($stub, $className)
            ->replaceUses($stub)
            ->replaceTraits($stub)
            ->replaceMethods($stub)
            ->replaceClass($stub, $className);
    }

    protected function getStub(): string
    {
        return __DIR__ . '/../../../stubs/augmentation.stub';
    }

    protected function replaceNamespace(string &$stub, string $className): self
    {
        $stub = str_replace('{{ namespace }}', 'App\\Augmentations', $stub);

        return $this;
    }

    protected function replaceUses(string &$stub): self
    {
        $uses = [];

        if ($this->shouldIncludeTrait('form')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesFormSchema;';
        }

        if ($this->shouldIncludeTrait('columns')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesTableColumns;';
        }

        if ($this->shouldIncludeTrait('filters')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesTableFilters;';
        }

        if ($this->shouldIncludeTrait('record-actions')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesRecordActions;';
        }

        if ($this->shouldIncludeTrait('header-actions')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesHeaderActions;';
        }

        if ($this->shouldIncludeTrait('toolbar-actions')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesToolbarActions;';
        }

        if ($this->shouldIncludeTrait('bulk-actions')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesBulkActions;';
        }

        if ($this->shouldIncludeTrait('casts')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesModelCasts;';
        }

        if ($this->shouldIncludeTrait('relations')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesModelRelations;';
        }

        if ($this->shouldIncludeTrait('relation-managers')) {
            $uses[] = 'use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesRelationManagers;';
        }

        $usesString = empty($uses) ? '' : implode("\n", $uses) . "\n";

        $stub = str_replace('{{ uses }}', $usesString, $stub);

        return $this;
    }

    protected function replaceTraits(string &$stub): self
    {
        $traits = [];

        if ($this->shouldIncludeTrait('form')) {
            $traits[] = 'ModifiesFormSchema';
        }

        if ($this->shouldIncludeTrait('columns')) {
            $traits[] = 'ModifiesTableColumns';
        }

        if ($this->shouldIncludeTrait('filters')) {
            $traits[] = 'ModifiesTableFilters';
        }

        if ($this->shouldIncludeTrait('record-actions')) {
            $traits[] = 'ModifiesRecordActions';
        }

        if ($this->shouldIncludeTrait('header-actions')) {
            $traits[] = 'ModifiesHeaderActions';
        }

        if ($this->shouldIncludeTrait('toolbar-actions')) {
            $traits[] = 'ModifiesToolbarActions';
        }

        if ($this->shouldIncludeTrait('bulk-actions')) {
            $traits[] = 'ModifiesBulkActions';
        }

        if ($this->shouldIncludeTrait('casts')) {
            $traits[] = 'ModifiesModelCasts';
        }

        if ($this->shouldIncludeTrait('relations')) {
            $traits[] = 'ModifiesModelRelations';
        }

        if ($this->shouldIncludeTrait('relation-managers')) {
            $traits[] = 'ModifiesRelationManagers';
        }

        $traitsString = '';
        if (! empty($traits)) {
            $traitsString = '    use ' . implode(";\n    use ", $traits) . ";\n\n";
        }

        $stub = str_replace('{{ traits }}', $traitsString, $stub);

        return $this;
    }

    protected function replaceMethods(string &$stub): self
    {
        $methods = [];

        if ($this->shouldIncludeTrait('form')) {
            $methods[] = $this->getFormSchemaMethod();
        }

        if ($this->shouldIncludeTrait('columns')) {
            $methods[] = $this->getColumnsMethod();
        }

        if ($this->shouldIncludeTrait('filters')) {
            $methods[] = $this->getFiltersMethod();
        }

        if ($this->shouldIncludeTrait('record-actions')) {
            $methods[] = $this->getRecordActionsMethod();
        }

        if ($this->shouldIncludeTrait('header-actions')) {
            $methods[] = $this->getHeaderActionsMethod();
        }

        if ($this->shouldIncludeTrait('toolbar-actions')) {
            $methods[] = $this->getToolbarActionsMethod();
        }

        if ($this->shouldIncludeTrait('bulk-actions')) {
            $methods[] = $this->getBulkActionsMethod();
        }

        if ($this->shouldIncludeTrait('casts')) {
            $methods[] = $this->getCastsMethod();
        }

        if ($this->shouldIncludeTrait('relations')) {
            $methods[] = $this->getRelationsMethod();
        }

        if ($this->shouldIncludeTrait('relation-managers')) {
            $methods[] = $this->getRelationManagersMethod();
        }

        $methodsString = empty($methods) ? '' : implode("\n", $methods);

        $stub = str_replace('{{ methods }}', $methodsString, $stub);

        return $this;
    }

    protected function replaceClass(string &$stub, string $className): string
    {
        return str_replace('{{ class }}', $className, $stub);
    }

    protected function shouldIncludeTrait(string $trait): bool
    {
        return $this->option('all') || $this->option($trait);
    }

    protected function getFormSchemaMethod(): string
    {
        return <<<'PHP'
    public static function getSchema(): array
    {
        return [
            // Add form fields here
        ];
    }

PHP;
    }

    protected function getColumnsMethod(): string
    {
        return <<<'PHP'
    public static function getColumns(): array
    {
        return [
            // Add table columns here
        ];
    }

PHP;
    }

    protected function getFiltersMethod(): string
    {
        return <<<'PHP'
    public static function getFilters(): array
    {
        return [
            // Add table filters here
        ];
    }

PHP;
    }

    protected function getRecordActionsMethod(): string
    {
        return <<<'PHP'
    public static function getRecordActions(): array
    {
        return [
            // Add record (row) actions here
        ];
    }

PHP;
    }

    protected function getHeaderActionsMethod(): string
    {
        return <<<'PHP'
    public static function getHeaderActions(): array
    {
        return [
            // Add header actions here
        ];
    }

PHP;
    }

    protected function getToolbarActionsMethod(): string
    {
        return <<<'PHP'
    public static function getToolbarActions(): array
    {
        return [
            // Add toolbar actions here
        ];
    }

PHP;
    }

    protected function getBulkActionsMethod(): string
    {
        return <<<'PHP'
    public static function getBulkActions(): array
    {
        return [
            // Add bulk actions here
        ];
    }

PHP;
    }

    protected function getCastsMethod(): string
    {
        return <<<'PHP'
    public static function getCasts(): array
    {
        return [
            // 'field_name' => 'cast_type',
        ];
    }

PHP;
    }

    protected function getRelationsMethod(): string
    {
        return <<<'PHP'
    public static function getRelations(): array
    {
        return [
            // 'relationName' => fn($model) => $model->hasMany(RelatedModel::class),
        ];
    }

PHP;
    }

    protected function getRelationManagersMethod(): string
    {
        return <<<'PHP'
    public static function getRelationManagers(): array
    {
        return [
            // RelationManager::class,
        ];
    }

PHP;
    }
}
