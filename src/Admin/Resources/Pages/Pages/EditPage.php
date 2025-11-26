<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Actions\Admin\GetPageListUrl;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Admin;
use SmartCms\Kit\Models\Page;
use SmartCms\Kit\Services\AI\OpenRouterService;
use SmartCms\Kit\Services\SEO\SeoAnalyzer;
use SmartCms\Kit\Services\SEO\SocialMediaPreview;
use SmartCms\Kit\Support\Contracts\PageStatus;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;
use SmartCms\Support\Admin\Components\Actions\ViewRecord;

class EditPage extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return __('kit::admin.edit_page') . ' ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            ActionGroup::make([
                SaveAction::make($this),
                SaveAndClose::make($this, GetPageListUrl::run($this->getRecord())),
                ViewRecord::make(),
                Action::make('preview')
                    ->label(__('kit::admin.preview_page'))
                    ->icon(Heroicon::Eye)
                    ->color('info')
                    ->url(fn(Page $record): ?string => $record->generatePreviewUrl())
                    ->openUrlInNewTab()
                    ->visible(fn(Page $record): bool => $record->status != PageStatus::Published),
                Action::make('clone')
                    ->label(__('kit::admin.clone_page'))
                    ->icon(Heroicon::DocumentDuplicate)
                    ->color('gray')
                    ->schema([
                        PageNameField::make()
                            ->default(fn(Page $record) => $record->name . ' (Copy)'),
                        PageSlugField::make()
                            ->default(fn(Page $record) => $record->slug . '-copy'),
                        Select::make('parent_id')
                            ->label(__('kit::admin.parent_page'))
                            ->options(function (Page $record) {
                                $maxDepth = config('kit.max_page_depth', 5);

                                return Page::query()
                                    ->where('id', '!=', $record->id)
                                    ->where('type', 'category')
                                    ->where('depth', '<', $maxDepth - 1)
                                    ->orderBy('slug')
                                    ->get()
                                    ->mapWithKeys(function (Page $page) use ($record) {
                                        // Exclude descendants
                                        if ($record->exists) {
                                            $descendantIds = $record->descendants()->pluck('id')->toArray();
                                            if (in_array($page->id, $descendantIds)) {
                                                return [];
                                            }
                                        }
                                        $indent = str_repeat('— ', $page->depth);
                                        $label = $indent . $page->name;

                                        return [$page->id => $label];
                                    })
                                    ->toArray();
                            })
                            ->default(fn(Page $record) => $record->parent_id)
                            ->searchable()
                            ->placeholder(__('kit::admin.no_parent')),
                    ])
                    ->action(function (Page $record, array $data): void {
                        // Clone the page
                        $clone = $record->replicate(['views', 'published_at']);
                        $clone->name = $data['name'];
                        $clone->slug = $data['slug'];
                        $clone->parent_id = $data['parent_id'] ?? null;
                        $clone->status = PageStatus::Draft;
                        $clone->published_at = null;
                        $clone->views = 0;

                        // Recalculate depth based on new parent
                        if ($clone->parent_id) {
                            $parent = Page::find($clone->parent_id);
                            $clone->depth = $parent ? $parent->depth + 1 : 0;
                        } else {
                            $clone->depth = 0;
                        }

                        $clone->save();

                        // Clone blocks relationship
                        foreach ($record->blocks as $block) {
                            $clone->blocks()->attach($block->id, [
                                'status' => $block->pivot->status,
                                'sorting' => $block->pivot->sorting,
                                'show_from' => $block->pivot->show_from,
                                'show_until' => $block->pivot->show_until,
                            ]);
                        }

                        // Clone template relationship
                        foreach ($record->template as $template) {
                            $clone->template()->create([
                                'section_id' => $template->section_id,
                                'sorting' => $template->sorting,
                            ]);
                        }

                        Notification::make()
                            ->success()
                            ->title(__('kit::admin.page_cloned_successfully'))
                            ->send();

                        $this->redirect(PageResource::getUrl('edit', ['record' => $clone]));
                    }),
                Action::make('generate_seo')
                    ->label('Generate SEO Fields')
                    ->icon(Heroicon::Sparkles)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Generate SEO Fields with AI')
                    ->modalDescription('This will use OpenRouter AI to generate meta description, keywords, and summary based on the page title and content.')
                    ->visible(fn() => app(OpenRouterService::class)->isConfigured())
                    ->action(function (Page $record): void {
                        $ai = app(OpenRouterService::class);

                        try {
                            $title = $record->getTranslation('title', main_lang()) ?? $record->getTranslation('name', main_lang());
                            $content = $record->getTranslation('content', main_lang());

                            $seoFields = $ai->generateSeoFields($title, $content);

                            // Only update empty fields
                            if (empty($record->getTranslation('description', main_lang()))) {
                                $record->setTranslation('description', main_lang(), $seoFields['description']);
                            }
                            if (empty($record->getTranslation('heading', main_lang()))) {
                                $record->setTranslation('heading', main_lang(), $seoFields['heading']);
                            }

                            // if (empty($record->getTranslation('keywords', main_lang()))) {
                            //     $record->setTranslation('keywords', main_lang(), $seoFields['keywords']);
                            // }

                            if (empty($record->getTranslation('summary', main_lang())) && $seoFields['summary']) {
                                $record->setTranslation('summary', main_lang(), $seoFields['summary']);
                            }

                            $record->save();

                            Notification::make()
                                ->success()
                                ->title('SEO fields generated successfully')
                                ->body('Meta description, keywords, and summary have been generated.')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Failed to generate SEO fields')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('translate_content')
                    ->label('Translate to All Languages')
                    ->icon(Heroicon::Language)
                    ->color('info')
                    ->requiresConfirmation()
                    ->modalHeading('Translate Content')
                    ->modalDescription('This will translate all fields to other configured languages. Only empty fields will be filled.')
                    ->visible(fn() => app(OpenRouterService::class)->isConfigured() && app('lang')->adminLanguages()->count() > 1)
                    ->action(function (Page $record): void {
                        $ai = app(OpenRouterService::class);

                        try {
                            $translations = $ai->translatePage($record);

                            $updatedCount = 0;
                            foreach ($translations as $lang => $fields) {
                                foreach ($fields as $field => $value) {
                                    $record->setTranslation($field, $lang, $value);
                                    $updatedCount++;
                                }
                            }

                            $record->save();

                            Notification::make()
                                ->success()
                                ->title('Content translated successfully')
                                ->body("Translated {$updatedCount} fields to other languages.")
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Translation failed')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
                Action::make('seo_health_check')
                    ->label('SEO Health Check')
                    ->icon(Heroicon::ChartBar)
                    ->color('warning')
                    ->modalHeading('SEO Health Check Report')
                    ->modalDescription('Comprehensive SEO analysis with AI-powered improvement suggestions')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(function (Page $record): array {
                        // Run SEO analysis with AI suggestions
                        $aiEnabled = app(OpenRouterService::class)->isConfigured();
                        $analyzer = new SeoAnalyzer($record, withAiSuggestions: $aiEnabled);
                        $analysis = $analyzer->analyze();
                        $textContent = $analyzer->formatAsText($analysis);

                        return [
                            Text::make(fn() => new \Illuminate\Support\HtmlString(
                                '<div style="white-space: pre-wrap; font-family: monospace; font-size: 0.875rem; line-height: 1.5;">' .
                                    nl2br(htmlspecialchars($textContent)) .
                                    '</div>'
                            ))
                                ->columnSpanFull(),
                        ];
                    }),
                Action::make('social_media_preview')
                    ->label('Social Media Preview')
                    ->icon(Heroicon::Share)
                    ->color('info')
                    ->modalHeading('Social Media Preview')
                    ->modalDescription('Preview how your page will appear when shared on social media')
                    ->modalWidth('3xl')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->schema(function (Page $record): array {
                        // Generate social media previews
                        $preview = new SocialMediaPreview($record);
                        $previews = $preview->generatePreviews();
                        $formattedPreviews = $preview->formatAsText($previews);

                        return [
                            Tabs::make('social_previews')
                                ->tabs([
                                    Tab::make('Google')
                                        ->icon(Heroicon::MagnifyingGlass)
                                        ->schema([
                                            Text::make(fn() => new \Illuminate\Support\HtmlString(
                                                $formattedPreviews['google']
                                            ))
                                                ->columnSpanFull(),
                                        ]),
                                    Tab::make('Facebook')
                                        ->icon(Heroicon::AtSymbol)
                                        ->schema([
                                            Text::make(fn() => new \Illuminate\Support\HtmlString($formattedPreviews['facebook']))
                                                ->columnSpanFull(),
                                        ]),
                                    Tab::make('Twitter')
                                        ->icon(Heroicon::ChatBubbleLeft)
                                        ->schema([
                                            Text::make(fn() => new \Illuminate\Support\HtmlString(
                                                $formattedPreviews['twitter']))
                                                ->columnSpanFull(),
                                        ]),
                                    Tab::make('LinkedIn')
                                        ->icon(Heroicon::Briefcase)
                                        ->schema([
                                            Text::make(fn() => new \Illuminate\Support\HtmlString($formattedPreviews['linkedin']))
                                                ->columnSpanFull(),
                                        ]),
                                ])
                                ->contained(false),
                        ];
                    }),
                DeleteAction::make()->hidden(fn(Page $record): bool => $record->is_system || $record->is_root),
                Action::make('change_published_at')
                    ->label(__('kit::admin.change_published_date'))
                    ->icon(Heroicon::Calendar)
                    ->color('info')
                    ->schema([
                        DateTimePicker::make('published_at')
                            ->label(__('kit::admin.published_at'))
                            ->default(fn(Page $record) => $record->published_at)
                            ->required(),
                    ])
                    ->action(function (Page $record, array $data): void {
                        $record->published_at = $data['published_at'];
                        $record->save();
                    }),
                Action::make('show info')->label(__('kit::admin.show_info'))->icon(Heroicon::InformationCircle)->color('primary')->schema([
                    Group::make([
                        TextEntry::make('created_at')->icon(Heroicon::OutlinedClock)->date(),
                        TextEntry::make('created_by')->icon(Heroicon::UserCircle)->formatStateUsing(function ($state) {
                            $admin = Admin::query()->find($state);

                            return $admin?->name ?? __('kit::admin.system');
                        }),
                        TextEntry::make('updated_at')->icon(Heroicon::OutlinedClock)->date(),
                        TextEntry::make('updated_by')->icon(Heroicon::OutlinedUserCircle)->formatStateUsing(function ($state) {
                            $admin = Admin::query()->find($state);

                            return $admin?->name ?? __('kit::admin.system');
                        }),
                    ])->columns(2),
                ]),
            ])->link()->label('Actions')
                ->icon(Heroicon::ChevronDown)
                ->size(Size::Small)
                ->iconPosition(IconPosition::After)
                ->color('primary'),

        ];
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.edit');
    }

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = [];

        // Add "Pages" link to list page
        $breadcrumbs[PageResource::getUrl('index')] = __('kit::admin.pages');

        // Add all ancestors with links to their edit pages
        $ancestors = $this->record->ancestors();
        foreach ($ancestors as $ancestor) {
            $breadcrumbs[PageResource::getUrl('edit', ['record' => $ancestor->id])] = $ancestor->name;
        }

        // Add current page (no link)
        $breadcrumbs[] = $this->record->name;

        return $breadcrumbs;
    }
}
