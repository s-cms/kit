<?php

namespace SmartCms\Kit\Admin\Resources\BlockTemplates;

use BackedEnum;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use SmartCms\Kit\Admin\Enums\NavigationGroup;
use SmartCms\Kit\Admin\Resources\BlockTemplates\Pages\CreateBlockTemplate;
use SmartCms\Kit\Admin\Resources\BlockTemplates\Pages\EditBlockTemplate;
use SmartCms\Kit\Admin\Resources\BlockTemplates\Pages\ListBlockTemplates;
use SmartCms\Kit\Admin\Resources\BlockTemplates\Schemas\BlockTemplateForm;
use SmartCms\Kit\Admin\Resources\BlockTemplates\Tables\BlockTemplatesTable;
use SmartCms\Kit\Models\BlockTemplate;
use UnitEnum;

class BlockTemplateResource extends Resource
{
    protected static ?string $model = BlockTemplate::class;

    protected static string | BackedEnum | null $navigationIcon = LucideIcon::LayoutTemplate;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationGroup::Design;
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.block_templates');
    }

    public static function form(Schema $schema): Schema
    {
        return BlockTemplateForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlockTemplatesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBlockTemplates::route('/'),
            'create' => CreateBlockTemplate::route('/create'),
            'edit' => EditBlockTemplate::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
