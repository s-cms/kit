<?php

namespace SmartCms\Kit\Admin\Resources\Pages;

use BackedEnum;
use Filament\Resources\Pages\Page as PagesPage;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Admin\Resources\Pages\Pages\CreatePage;
use SmartCms\Kit\Admin\Resources\Pages\Pages\EditLayoutSettings;
use SmartCms\Kit\Admin\Resources\Pages\Pages\EditMenuSection as PagesEditMenuSection;
use SmartCms\Kit\Admin\Resources\Pages\Pages\EditPage;
use SmartCms\Kit\Admin\Resources\Pages\Pages\EditSeo;
use SmartCms\Kit\Admin\Resources\Pages\Pages\EditTemplateRelated;
use SmartCms\Kit\Admin\Resources\Pages\Pages\ListCategories;
use SmartCms\Kit\Admin\Resources\Pages\Pages\ListItems;
use SmartCms\Kit\Admin\Resources\Pages\Pages\ListPages;
use SmartCms\Kit\Admin\Resources\Pages\Schemas\PageForm;
use SmartCms\Kit\Admin\Resources\Pages\Tables\PagesTable;
use SmartCms\Kit\Models\Page;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?int $navigationSort = -1;

    protected static string | BackedEnum | null $navigationIcon = Heroicon::OutlinedRocketLaunch;

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.pages');
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // Add augmented relation managers from augmentations
            ...Page::getAugmentedRelationManagers(),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
            'items' => ListItems::route('/list/{record}/items'),
            'categories' => ListCategories::route('/list/{record}/categories'),
            'template' => EditTemplateRelated::route('/{record}/template'),
            // 'seo' => EditSeo::route('/{record}/seo'),
            'menu' => PagesEditMenuSection::route('/{record}/menu'),
            'layout' => EditLayoutSettings::route('/{record}/layout'),
        ];
    }

    public static function getRecordSubNavigation(PagesPage $page): array
    {
        $subNavigation = [
            EditPage::class,
            EditTemplateRelated::class,
        ];
        $schema = $page->record?->layout?->schema ?? [];
        if (count($schema) > 0) {
            $subNavigation[] = EditLayoutSettings::class;
        }

        return $page->generateNavigationItems($subNavigation);
    }

    /**
     * @param  Page  $record
     */
    public static function canDelete(Model $record): bool
    {
        return ! $record->is_system;
    }
}
