<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Pages;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\IconPosition;
use Filament\Support\Enums\Size;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Actions\Admin\GetPageNavigation;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;
use SmartCms\Support\Admin\Components\Actions\ViewRecord;
use SmartCms\TemplateBuilder\Models\Layout;
use SmartCms\TemplateBuilder\Models\Section as ModelsSection;

class EditMenuSection extends EditRecord
{
    protected static string $resource = PageResource::class;

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.menu_section_settings');
    }

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return 'heroicon-o-cog';
    }

    public function getBreadcrumbs(): array
    {
        return [];
    }

    public function form(Schema $schema): Schema
    {
        $itemsLayouts = Layout::query()->where('path', 'like', '%pages/%')->pluck('name', 'id')->toArray();
        $categoriesLayouts = Layout::query()->where('path', 'like', '%divisions/%')->pluck('name', 'id')->toArray();

        return $schema->components([
            Hidden::make('settings.is_categories')->formatStateUsing(fn ($state) => $state ?? false),
            Section::make(__('kit::admin.categories'))->schema([
                Select::make('settings.categories_layout_id')
                    ->label(__('kit::admin.categories_layout'))
                    ->required()
                    ->options($categoriesLayouts),
                Repeater::make('settings.categories_template')->schema([
                    Select::make('section_id')
                        ->label(__('kit::admin.section'))
                        ->options(ModelsSection::query()->pluck('name', 'id')->toArray())->required(),
                ]),
            ])->hidden(fn ($get): bool => ! $get('settings.is_categories')),
            Section::make(__('kit::admin.items'))->compact()->schema([
                Select::make('settings.items_layout_id')
                    ->label(__('kit::admin.items_layout'))
                    ->required()
                    ->options($itemsLayouts),
                Repeater::make('settings.items_template')->schema([
                    Select::make('section_id')
                        ->label(__('kit::admin.section'))
                        ->options(ModelsSection::query()->pluck('name', 'id')->toArray())->required(),
                ]),
            ]),
        ])->columns(1);
    }

    public function getHeading(): string | Htmlable
    {
        return __('kit::admin.edit') . ' ' . $this->record->name;
    }

    protected function getHeaderActions(): array
    {

        return [
            ActionGroup::make([
                Action::make('delete_menu_section')->label(__('kit::admin.delete'))->icon('heroicon-o-trash')
                    ->color('danger')
                    ->disabled(fn($record) => Page::query()->where('root_id', $this->record->id)->exists())
                    ->requiresConfirmation()
                    ->action(function ($record): \Illuminate\Routing\Redirector|\Illuminate\Http\RedirectResponse {
                        $record->delete();
                        Notification::make()->title(__('kit::admin.success'))->success()->send();

                        return redirect(ListPages::getUrl());
                    }),
                Action::make('transfer')->label(__('kit::admin.transfer'))->icon('heroicon-o-arrows-right-left')
                    ->color('danger')
                    ->schema(fn($form) => $form->schema([
                        Select::make('root_id')
                            ->label(__('kit::admin.menu_section'))
                            ->options(Page::query()->where('id', '!=', $this->record->id)->whereJsonContains('settings->is_categories', $this->record->settings['is_categories'])->pluck('name', 'id')->toArray())
                            ->required(),
                    ]))->action(function (array $data): void {
                        Page::query()->where('root_id', $this->record->id)->update([
                            'root_id' => $data['root_id'],
                        ]);
                        Notification::make()->title(__('kit::admin.success'))->success()->send();
                    }),
                EditAction::make()->url(fn ($record): string => EditPage::getUrl(['record' => $record->id])),
                ViewRecord::make(),
                SaveAndClose::make($this, ListPages::getUrl()),
                SaveAction::make($this),
            ])->link()->label('Actions')
                ->icon(Heroicon::ChevronDown)
                ->size(Size::Small)
                ->iconPosition(IconPosition::After)
                ->color('primary'),
        ];
    }

    public function getSubNavigation(): array
    {
        return GetPageNavigation::run();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if ($record->settings['is_categories']) {
            Page::query()->where('parent_id', $record->id)->update([
                'layout_id' => $data['settings']['categories_layout_id'],
            ]);
            Page::query()->where('parent_id', '!=', $record->id)->where('root_id', $record->id)->update([
                'layout_id' => $data['settings']['items_layout_id'],
            ]);
        } else {
            Page::query()->where('parent_id', $record->id)->update([
                'layout_id' => $data['settings']['items_layout_id'],
            ]);
        }
        parent::handleRecordUpdate($record, $data);

        return $record;
    }
}
