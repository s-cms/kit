<?php

namespace SmartCms\Kit\Support\Augmentation;

use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesBulkActions;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesFormSchema;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesHeaderActions;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesModelCasts;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesModelRelations;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesRecordActions;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesRelationManagers;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesTableColumns;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesTableFilters;
use SmartCms\Kit\Support\Augmentation\Concerns\ModifiesToolbarActions;

trait HasAugmentations
{
    protected static bool $augmentationsBooted = false;

    /**
     * Boot augmentations.
     */
    public static function bootHasAugmentations(): void
    {
        if (static::$augmentationsBooted) {
            return;
        }

        static::applyAugmentations();
        static::$augmentationsBooted = true;
    }

    /**
     * Apply all registered augmentations.
     */
    protected static function applyAugmentations(): void
    {
        $augmentations = static::getAugmentationClasses();

        foreach ($augmentations as $class) {
            // Apply casts (if trait is used)
            if (static::usesTrait($class, ModifiesModelCasts::class)) {
                foreach ($class::getCasts() as $key => $cast) {
                    // static::addDynamicCast($key, $cast);
                }
            }

            // Apply relationships (if trait is used)
            if (static::usesTrait($class, ModifiesModelRelations::class)) {
                foreach ($class::getRelations() as $name => $callback) {
                    static::resolveRelationUsing($name, $callback);
                }
            }
        }
    }

    /**
     * Get augmentation classes for this model.
     *
     * @return array<class-string<AbstractAugmentation>>
     */
    protected static function getAugmentationClasses(): array
    {
        $configKey = static::getAugmentationConfigKey();

        return config($configKey, []);
    }

    /**
     * Get the config key for augmentations.
     * Override in child classes for different models.
     */
    protected static function getAugmentationConfigKey(): string
    {
        return 'augmentations.page';
    }

    /**
     * Check if a class uses a specific trait.
     */
    protected static function usesTrait(string $class, string $trait): bool
    {
        $uses = class_uses_recursive($class);

        return in_array($trait, $uses ?: []);
    }

    // ===== FILAMENT ADMIN PANEL HELPERS =====

    /**
     * Get all admin form schema components from augmentations.
     *
     * @return array<\Filament\Forms\Components\Component>
     */
    public static function getAugmentedSchema(): array
    {
        $schema = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesFormSchema::class)) {
                $schema = array_merge($schema, $class::getSchema());
            }
        }

        return $schema;
    }

    /**
     * Get all admin table columns from augmentations.
     *
     * @return array<\Filament\Tables\Columns\Column>
     */
    public static function getAugmentedColumns(): array
    {
        $columns = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesTableColumns::class)) {
                $columns = array_merge($columns, $class::getColumns());
            }
        }

        return $columns;
    }

    /**
     * Get all admin table filters from augmentations.
     *
     * @return array<\Filament\Tables\Filters\Filter>
     */
    public static function getAugmentedFilters(): array
    {
        $filters = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesTableFilters::class)) {
                $filters = array_merge($filters, $class::getFilters());
            }
        }

        return $filters;
    }

    /**
     * Get all admin table record actions from augmentations (Filament 4).
     *
     * @return array<\Filament\Tables\Actions\Action>
     */
    public static function getAugmentedRecordActions(): array
    {
        $actions = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesRecordActions::class)) {
                $actions = array_merge($actions, $class::getRecordActions());
            }
        }

        return $actions;
    }

    /**
     * Get all admin table header actions from augmentations (Filament 4).
     *
     * @return array<\Filament\Tables\Actions\Action>
     */
    public static function getAugmentedHeaderActions(): array
    {
        $actions = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesHeaderActions::class)) {
                $actions = array_merge($actions, $class::getHeaderActions());
            }
        }

        return $actions;
    }

    /**
     * Get all admin table toolbar actions from augmentations (Filament 4).
     *
     * @return array<\Filament\Tables\Actions\Action>
     */
    public static function getAugmentedToolbarActions(): array
    {
        $actions = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesToolbarActions::class)) {
                $actions = array_merge($actions, $class::getToolbarActions());
            }
        }

        return $actions;
    }

    /**
     * Get all admin table bulk actions from augmentations.
     *
     * @return array<\Filament\Tables\Actions\BulkAction>
     */
    public static function getAugmentedBulkActions(): array
    {
        $bulkActions = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesBulkActions::class)) {
                $bulkActions = array_merge($bulkActions, $class::getBulkActions());
            }
        }

        return $bulkActions;
    }

    /**
     * Get all relation managers from augmentations.
     *
     * @return array<class-string<\Filament\Resources\RelationManagers\RelationManager>>
     */
    public static function getAugmentedRelationManagers(): array
    {
        $relationManagers = [];

        foreach (static::getAugmentationClasses() as $class) {
            if (static::usesTrait($class, ModifiesRelationManagers::class)) {
                $relationManagers = array_merge($relationManagers, $class::getRelationManagers());
            }
        }

        return $relationManagers;
    }

    /**
     * Apply all augmentation transformations to the context.
     * Transform is ALWAYS available (in base AbstractAugmentation).
     *
     * @param \SmartCms\Kit\Support\Transformers\TransformContext $context
     * @return void
     */
    public static function applyAugmentedTransformations($context): void
    {
        foreach (static::getAugmentationClasses() as $class) {
            $class::transform($context); // Always available
        }
    }
}
