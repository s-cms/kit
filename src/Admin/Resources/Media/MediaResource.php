<?php

namespace SmartCms\Kit\Admin\Resources\Media;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use SmartCms\Kit\Admin\Enums\NavigationGroup;
use SmartCms\Kit\Admin\Resources\Media\Pages\EditMedia;
use SmartCms\Kit\Admin\Resources\Media\Pages\ListMedia;
use SmartCms\Kit\Admin\Resources\Media\Schemas\MediaForm;
use SmartCms\Kit\Admin\Resources\Media\Tables\MediaTable;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use UnitEnum;

class MediaResource extends Resource
{
    protected static ?string $model = Media::class;

    protected static ?int $navigationSort = 50;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedPhoto;

    public static function getNavigationGroup(): string | UnitEnum | null
    {
        return NavigationGroup::Content;
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.media_library');
    }

    public static function getModelLabel(): string
    {
        return __('kit::admin.media');
    }

    public static function getPluralModelLabel(): string
    {
        return __('kit::admin.media_library');
    }

    public static function form(Schema $schema): Schema
    {
        return MediaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MediaTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMedia::route('/'),
            'edit' => EditMedia::route('/{record}/edit'),
        ];
    }
}
