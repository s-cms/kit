<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Schemas;

use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieTagsInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use SmartCms\Kit\Forms\Components\MediaPicker;
use SmartCms\Kit\Models\BlockTemplate;
use SmartCms\Kit\Models\Page as ModelsPage;
use SmartCms\Kit\Support\Contracts\PageStatus;

class PageSummary extends Page
{
    public static function make(): array
    {
        $imagePath = '';

        return [
            Section::make('Status')->icon(function (Get $get): \Filament\Support\Icons\Heroicon {
                $status = $get('status');

                return match ($status) {
                    'draft' => Heroicon::OutlinedSun,
                    'scheduled' => Heroicon::OutlinedCalendarDays,
                    default => Heroicon::Sun,
                };
            })->compact()
                ->schema([
                    Radio::make('status')->hiddenLabel()
                        ->disabled(fn ($record): bool => $record->id == 1)
                        ->options(PageStatus::class)->default('active')->reactive(),
                    DateTimePicker::make('published_at')->reactive()->seconds(false)->default(now())->hidden(fn ($get): bool => $get('status')?->value != 'scheduled'),
                ]),
            Section::make()->compact()->schema([
                MediaPicker::make('image')->label(__('kit::admin.image')),
                MediaPicker::make('banner')->label(__('kit::admin.banner')),
            ])->columns(1),
            Section::make(__('kit::admin.indexation'))->icon(function (Get $get): \Filament\Support\Icons\Heroicon {
                $index = $get('is_index') ?? true;

                return match ($index) {
                    true => Heroicon::OutlinedMagnifyingGlass,
                    default => Heroicon::OutlinedMagnifyingGlassMinus
                };
            })->compact()->schema([
                Toggle::make('is_index')->label(__('kit::admin.is_index'))->hiddenLabel()->default(true)->reactive(),
            ]),
            SpatieTagsInput::make('tags')
                ->type('tag')
                ->label(__('kit::admin.tags')),
            Section::make(__('kit::admin.child_templates'))
                ->icon(Heroicon::Squares2x2)
                ->compact()
                ->visible(fn (Get $get, ?ModelsPage $record) => $record?->canHaveChildren() ?? in_array($get('type'), ['category']))
                ->schema([
                    Select::make('settings.child_template_page')
                        ->label(__('kit::admin.template_for_child_pages'))
                        ->helperText(__('kit::admin.template_for_child_pages_helper'))
                        ->options(function (Get $get) {
                            $type = $get('type');

                            return BlockTemplate::query()
                                ->where(function ($query) {
                                    $query->where('type', 'page')
                                        ->orWhereNull('type');
                                })
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->hintAction(
                            Action::make('force_apply_pages')
                                ->label(__('kit::admin.force_apply'))
                                ->icon(Heroicon::Bolt)
                                ->color('warning')
                                ->requiresConfirmation()
                                ->modalHeading(__('kit::admin.force_apply_template_to_pages'))
                                ->modalDescription(__('kit::admin.force_apply_template_description'))
                                ->action(function (Get $get, ?ModelsPage $record) {
                                    $templateId = $get('settings.child_template_page');
                                    if (! $templateId || ! $record) {
                                        return;
                                    }

                                    $template = BlockTemplate::find($templateId);
                                    if (! $template) {
                                        return;
                                    }

                                    $children = $record->children()->where('type', 'page')->get();
                                    $count = 0;

                                    foreach ($children as $child) {
                                        $template->applyToPage($child);
                                        $count++;
                                    }

                                    Notification::make()
                                        ->title(__('kit::admin.template_applied_successfully'))
                                        ->body(__('kit::admin.template_applied_to_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn (Get $get, ?ModelsPage $record) => $record && $get('settings.child_template_page'))
                        ),
                    Select::make('settings.child_template_category')
                        ->label(__('kit::admin.template_for_child_categories'))
                        ->helperText(__('kit::admin.template_for_child_categories_helper'))
                        ->options(function (Get $get) {
                            $type = $get('type');

                            return BlockTemplate::query()
                                ->where(function ($query) {
                                    $query->where('type', 'category')
                                        ->orWhereNull('type');
                                })
                                ->pluck('name', 'id');
                        })
                        ->searchable()
                        ->hintAction(
                            Action::make('force_apply_categories')
                                ->label(__('kit::admin.force_apply'))
                                ->icon(Heroicon::Bolt)
                                ->color('warning')
                                ->requiresConfirmation()
                                ->modalHeading(__('kit::admin.force_apply_template_to_categories'))
                                ->modalDescription(__('kit::admin.force_apply_template_description'))
                                ->action(function (Get $get, ?ModelsPage $record) {
                                    $templateId = $get('settings.child_template_category');
                                    if (! $templateId || ! $record) {
                                        return;
                                    }

                                    $template = BlockTemplate::find($templateId);
                                    if (! $template) {
                                        return;
                                    }

                                    $children = $record->children()->where('type', 'category')->get();
                                    $count = 0;

                                    foreach ($children as $child) {
                                        $template->applyToPage($child);
                                        $count++;
                                    }

                                    Notification::make()
                                        ->title(__('kit::admin.template_applied_successfully'))
                                        ->body(__('kit::admin.template_applied_to_count', ['count' => $count]))
                                        ->success()
                                        ->send();
                                })
                                ->visible(fn (Get $get, ?ModelsPage $record) => $record && $get('settings.child_template_category'))
                        ),
                ]),
        ];
    }
}
