<?php

namespace SmartCms\Kit\Admin\Resources\Tags;

use BackedEnum;
use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use SmartCms\Kit\Admin\Enums\NavigationGroup;
use SmartCms\Kit\Admin\Resources\Tags\Pages\CreateTag;
use SmartCms\Kit\Admin\Resources\Tags\Pages\EditTag;
use SmartCms\Kit\Admin\Resources\Tags\Pages\ListTags;
use SmartCms\Kit\Admin\Resources\Tags\Schemas\TagForm;
use SmartCms\Kit\Admin\Resources\Tags\Tables\TagsTable;
use SmartCms\Kit\Models\Tag;
use UnitEnum;

class TagResource extends Resource
{
    protected static ?string $model = Tag::class;

    protected static string | BackedEnum | null $navigationIcon = LucideIcon::Tags;

    public static function getNavigationBadge(): ?string
    {
        return Tag::query()->count();
    }

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationGroup::Design;
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.tags');
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return TagForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TagsTable::configure($table);
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
            'index' => ListTags::route('/'),
            'create' => CreateTag::route('/create'),
            'edit' => EditTag::route('/{record}/edit'),
        ];
    }
}
