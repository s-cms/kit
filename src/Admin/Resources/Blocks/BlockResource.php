<?php

namespace SmartCms\Kit\Admin\Resources\Blocks;

use BackedEnum;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use SmartCms\Kit\Admin\Clusters\Design\DesignCluster;
use SmartCms\Kit\Admin\Resources\Blocks\Pages\CreateBlock;
use SmartCms\Kit\Admin\Resources\Blocks\Pages\EditBlock;
use SmartCms\Kit\Admin\Resources\Blocks\Pages\ListBlocks;
use SmartCms\Kit\Admin\Resources\Blocks\Schemas\BlockForm;
use SmartCms\Kit\Admin\Resources\Blocks\Tables\BlocksTable;
use SmartCms\Kit\Models\Block;

class BlockResource extends Resource
{
    protected static ?string $model = Block::class;

    protected static string | BackedEnum | null $navigationIcon = LucideIcon::Blocks;

    public static function getCluster(): ?string
    {
        return DesignCluster::class;
    }

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return BlockForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BlocksTable::configure($table);
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
            'index' => ListBlocks::route('/'),
            'create' => CreateBlock::route('/create'),
            'edit' => EditBlock::route('/{record}/edit'),
        ];
    }
}
