<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Pages;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Actions\Admin\GetPageListUrl;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Models\Page;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;
use SmartCms\Support\Admin\Components\Actions\ViewRecord;
use SmartCms\Support\Admin\Components\Tables\SortingColumn;
use SmartCms\Support\Admin\Components\Tables\StatusColumn;
use SmartCms\Support\Admin\Components\Tables\UpdatedAtColumn;
use SmartCms\TemplateBuilder\Models\Section as ModelsSection;

class EditTemplateRelated extends ManageRelatedRecords
{
    protected static string $resource = PageResource::class;

    protected static string $relationship = 'template';

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.sections');
    }

    public static function getNavigationIcon(): string | Htmlable | null
    {
        return 'heroicon-m-light-bulb';
    }

    public function getTitle(): string
    {
        return __('kit::admin.edit_page') . ' ' . $this->record->name;
    }

    public static function getNavigationBadge(): ?string
    {
        $pageId = request()->route('record', 0);

        return Page::query()->find($pageId)?->template()->count() ?? 0;
    }

    public function getBreadcrumb(): string
    {
        return $this->record->name;
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components($form->getRecord()->section?->schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function ($query) {
                return $query->withoutGlobalScopes()->orderBy('sorting', 'asc');
            })
            ->recordTitleAttribute('name')
            ->reorderable('sorting')
            ->columns([
                TextColumn::make('section.name')->label(__('kit::admin.section')),
                StatusColumn::make(),
                SortingColumn::make(),
                UpdatedAtColumn::make(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make()->mutateRecordDataUsing(function (array $data, $record): array {
                    $data['value'] = $record->section?->getTranslations('value') ?? [];

                    return $data;
                })
                    ->mutateDataUsing(function (array $data, $record): array {
                        $originValue = $record->section?->value ?? [];
                        if (json_encode($originValue) == json_encode($data['value'])) {
                            return [];
                        }
                        $isUsed = $record->section->templates()->count() > 1;
                        if (! $isUsed) {
                            $record->section->update([
                                'value' => $data['value'],
                            ]);

                            return [];
                        }
                        $newSection = $record->section->replicate();
                        $freshName = explode(' - ', $newSection->name)[0];

                        $newSection->name = $freshName . ' - ' . $this->record->name;
                        if (ModelsSection::query()->where('name', $newSection->name)->exists()) {
                            $newSection->name = $freshName . ' - ' . $this->record->name . ' ' . $record->id;
                        }
                        $newSection->value = $data['value'];
                        $newSection->save();
                        $record->section_id = $newSection->id;
                        $record->save();

                        return [];

                        return [];
                    }),
                // DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
                CreateAction::make()->label(__('kit::admin.new_section'))
                    ->link()
                    ->schema([
                        Select::make('sections')
                            ->options(ModelsSection::query()->pluck('name', 'id')->toArray())
                            ->multiple()
                            ->label(__('kit::admin.section'))
                            ->required(),
                    ])->createAnother(false)
                    ->using(function (array $data, string $model): Model {
                        $maxSorting = $this->getRecord()->template()->max('sorting') ?? 0;
                        $sorting = $maxSorting + 1;
                        foreach ($data['sections'] as $section) {
                            $this->record->template()->create([
                                'section_id' => (int) $section,
                                'sorting' => $sorting,
                            ]);
                            $sorting++;
                        }

                        return $this->getRecord();
                    }),
            ])
            ->paginated(false);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ActionGroup::make([
                SaveAction::make($this),
                SaveAndClose::make($this, GetPageListUrl::run($this->getRecord())),
                ViewRecord::make(),
                DeleteAction::make(),
            ])->link()->label('Actions')
                ->icon(\Filament\Support\Icons\Heroicon::ChevronDown)
                ->size(\Filament\Support\Enums\Size::Small)
                ->iconPosition(\Filament\Support\Enums\IconPosition::After)
                ->color('primary'),
        ];
    }
}
