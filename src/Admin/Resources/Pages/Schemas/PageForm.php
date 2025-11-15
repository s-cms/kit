<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Admin\Components\Actions\AiAction;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Services\AI\OpenRouterService;
use SmartCms\Support\Admin\Components\Layout\FormGrid;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;
use Spatie\Tags\Tag;

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
                            //     TagsInput::make('tags')
                            //         ->label(__('kit::admin.tags'))
                            //         ->placeholder(__('kit::admin.tags_placeholder'))
                            //         ->suggestions(fn () => Tag::pluck('name->' . main_lang())->toArray())
                            //         ->newestFirst()
                            //         ->afterStateHydrated(function (TagsInput $component, $state, ?Page $record) {
                            //             if ($record && $record->exists) {
                            //                 $tagNames = $record->tags->pluck('name->' . main_lang())->toArray();
                            //                 $component->state($tagNames);
                            //             }
                            //         })
                            //         ->dehydrated(false)
                            //         ->saveRelationshipsUsing(function (Page $record, $state) {
                            //             if (! $state) {
                            //                 return;
                            //             }

                            //             $tagIds = collect($state)->map(function ($tagName) {
                            //                 $tag = Tag::findOrCreate($tagName, null, main_lang());

                            //                 return $tag->id;
                            //             })->toArray();

                            //             $record->syncTags($tagIds);
                            //         }),
                            ]),
                            Tabs::make('seo')->schema(
                                app('lang')->adminLanguages()->map(function ($language) {
                                    return Tab::make($language->name)->schema([
                                        // Action::make('translate_with_ai')
                                        //     ->label('Translate with AI')
                                        //     ->icon(Heroicon::Language)
                                        //     ->color('info')
                                        //     ->action(function (Page $record, Set $set) use ($language) {
                                        //         $ai = app(OpenRouterService::class);
                                        //         $title = $record->getTranslation('title', $language->slug) ?? $record->getTranslation('name', $language->slug);
                                        //         $content = $record->getTranslation('content', $language->slug);
                                        //         $translation = $ai->translate($content, $language->slug);
                                        //         $set('title.' . $language->slug, $translation);
                                        //     }),
                                        TextInput::make('title.' . $language->slug)
                                            ->label(__('seo::admin.seo_title'))
                                            ->required()
                                            ->rules('string', 'max:255')
                                            // ->characterLimit(255)
                                            ->maxLength(255),
                                        TextInput::make('heading.' . $language->slug)
                                            ->label(__('seo::admin.seo_heading'))
                                            ->hintAction(
                                                AiAction::make('generate_heading')
                                                    ->label('Generate Heading')
                                                    ->action(function (Page $record, Set $set) use ($language) {
                                                        $ai = app(OpenRouterService::class);
                                                        $title = $record->getTranslation('title', $language->slug) ?? $record->getTranslation('name', $language->slug);
                                                        $content = $record->getTranslation('content', $language->slug);
                                                        $heading = $ai->generateHeading($title, $content);
                                                        $set('heading.' . $language->slug, $heading);
                                                    }),
                                            )
                                            ->rules('string', 'max:255')
                                            // ->characterLimit(255)
                                            ->maxLength(255),
                                        Textarea::make('description.' . $language->slug)
                                            ->label(__('seo::admin.seo_description'))
                                            ->rules('string', 'max:255')
                                            ->hintAction(
                                                AiAction::make('generate_description')
                                                    ->label('Generate Description')
                                                    ->action(function (Page $record, Set $set) use ($language) {
                                                        $ai = app(OpenRouterService::class);
                                                        $title = $record->getTranslation('title', $language->slug) ?? $record->getTranslation('name', $language->slug);
                                                        $content = $record->getTranslation('content', $language->slug);
                                                        $description = $ai->generateMetaDescription($title, $content);
                                                        $set('description.' . $language->slug, $description);
                                                    }),
                                            )
                                            // ->characterLimit(255)
                                            ->maxLength(255),
                                        Textarea::make('summary.' . $language->slug)
                                            ->label(__('seo::admin.seo_summary'))
                                            ->rules('string', 'max:500')
                                            ->hintAction(
                                                AiAction::make('generate_summary')
                                                    ->label('Generate Summary')
                                                    ->action(function (Page $record, Set $set) use ($language) {
                                                        $ai = app(OpenRouterService::class);
                                                        $title = $record->getTranslation('title', $language->slug) ?? $record->getTranslation('name', $language->slug);
                                                        $content = $record->getTranslation('content', $language->slug);
                                                        $summary = $ai->generateSummary($title, $content);
                                                        $set('summary.' . $language->slug, $summary);
                                                    }),
                                            )
                                            ->maxLength(500),
                                        RichEditor::make('content.' . $language->slug)
                                            ->label(__('seo::admin.seo_content'))
                                            // ->rules('string')
                                            ->columnSpanFull(),
                                    ]);
                                })->toArray()
                            ),
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
