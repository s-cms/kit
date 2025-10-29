<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;
use SmartCms\Kit\Actions\Admin\GetPageListUrl;
use SmartCms\Kit\Admin\Resources\Pages\PageResource;
use SmartCms\Kit\Admin\Resources\Pages\Schemas\PageSummary;
use SmartCms\Support\Admin\Components\Actions\SaveAction;
use SmartCms\Support\Admin\Components\Actions\SaveAndClose;
use SmartCms\Support\Admin\Components\Actions\ViewRecord;
use SmartCms\Support\Admin\Components\Layout\FormGrid;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;
use SmartCms\TemplateBuilder\Models\Layout;

class EditLayoutSettings extends EditRecord
{
    protected static string $resource = PageResource::class;

    public function getTitle(): string
    {
        return __('kit::admin.edit_page') . ' ' . $this->record->name;
    }

    public static function getNavigationLabel(): string
    {
        return __('kit::admin.layout_settings');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->schema([
            FormGrid::make()->schema([
                LeftGrid::make()->schema([
                    Flex::make(function (Get $get) {
                        $layout = Layout::find($get('layout_id'));

                        return $layout?->schema ?? [];
                    }),
                ]),
                RightGrid::make()->schema(PageSummary::make()),
            ]),
        ]);
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

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $toUpdate = [
            'layout_id' => $data['layout_id'] ?? $record->layout_id ?? null,
        ];
        if (isset($data['value'])) {
            $value = $data['value'];
            $layout = Layout::find($data['layout_id']);
            if ($layout->getTranslations('value') != $value) {
                $toUpdate['layout_settings'] = $value;
            }
        }
        $this->record->update($toUpdate);

        return $record;
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['value'] = $this->record?->getTranslations('layout_settings') ?? [];
        if ($this->record?->layout && empty($data['value'])) {
            $data['value'] = $this->record->layout?->getTranslations('value') ?? [];
        }

        return $data;
    }
}
