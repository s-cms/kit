<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Schemas;

use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Models\Page;
use SmartCms\Seo\Admin\Seos\Schemas\RelatedSeoForm;
use SmartCms\Support\Admin\Components\Layout\FormGrid;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        $imagePath = '';
        /**
         * @var Page $record
         */
        $record = $schema->getRecord();
        if ($record?->slug) {
            $imagePath = 'pages/' . $record->slug;
        }

        return $schema
            ->components(
                [
                    FormGrid::make()->schema([
                        LeftGrid::make()->schema([
                            Section::make([
                                PageNameField::make(),
                                PageSlugField::make()->hidden(fn ($record): bool => $record?->id == 1),
                                Select::make('type')
                                    ->label(__('kit::admin.page_type'))
                                    ->options(self::getAvailableTypes())
                                    ->default('page')
                                    ->required()
                                    ->reactive()
                                    ->hidden(fn ($record): bool => $record?->id == 1),
                                Select::make('parent_id')
                                    ->label(__('kit::admin.parent_page'))
                                    ->options(fn ($record) => self::getParentOptions($record))
                                    ->searchable()
                                    ->placeholder(__('kit::admin.no_parent'))
                                    ->helperText(fn ($get) => self::getDepthHelperText($get('parent_id')))
                                    ->hidden(fn ($record): bool => $record?->id == 1),
                            ]),
                            ...RelatedSeoForm::configure($schema)->getComponents(),
                            // Add augmented schema from augmentations
                            ...Page::getAugmentedSchema(),
                        ]),
                        RightGrid::make()->schema(PageSummary::make()),
                    ]),
                ]
            )->columns(1);
    }

    /**
     * Get available page types for the type selector.
     */
    protected static function getAvailableTypes(): array
    {
        return [
            'page' => __('kit::admin.type_page'),
            'category' => __('kit::admin.type_category'),
        ];
    }

    /**
     * Get parent options with indentation to show hierarchy.
     */
    protected static function getParentOptions(?Page $record): array
    {
        $maxDepth = config('kit.max_page_depth', 5);

        return Page::query()
            ->where('id', '!=', $record?->id ?? 0) // Exclude self
            ->where('type', 'category') // Only types that can have children
            ->where('depth', '<', $maxDepth - 1) // Don't allow parents at max depth
            ->orderBy('slug')
            ->get()
            ->mapWithKeys(function (Page $page) use ($record) {
                // Exclude descendants if editing existing page
                if ($record && $record->exists) {
                    $descendantIds = $record->descendants()->pluck('id')->toArray();
                    if (in_array($page->id, $descendantIds)) {
                        return [];
                    }
                }

                // Create indented name based on depth
                $indent = str_repeat('— ', $page->depth);
                $label = $indent . $page->name . ' (' . $page->type . ')';

                return [$page->id => $label];
            })
            ->toArray();
    }

    /**
     * Get helper text showing depth information.
     */
    protected static function getDepthHelperText(?int $parentId): string
    {
        if (! $parentId) {
            return __('kit::admin.depth_helper_root');
        }

        $parent = Page::find($parentId);
        if (! $parent) {
            return '';
        }

        $maxDepth = config('kit.max_page_depth', 5);
        $currentDepth = $parent->depth + 1;
        $remaining = $maxDepth - $currentDepth;

        return __('kit::admin.depth_helper', [
            'current' => $currentDepth,
            'max' => $maxDepth,
            'remaining' => $remaining,
        ]);
    }
}
