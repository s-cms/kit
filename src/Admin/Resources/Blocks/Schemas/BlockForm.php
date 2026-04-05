<?php

namespace SmartCms\Kit\Admin\Resources\Blocks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use SmartCms\Kit\Services\Block\BlockService;
use SmartCms\Lang\Models\Language;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;

class BlockForm
{
    public static function configure(Schema $schema): Schema
    {
        $service = app(BlockService::class);

        return $schema
            ->components([
                Grid::make()->gridContainer()
                    ->columns([
                        '@md' => 3,
                        '@xl' => 4,
                    ])
                    ->columnSpanFull()
                    ->schema([
                        LeftGrid::make()->schema([
                            ...self::buildBlockLanguageSchema($service),
                            Text::make(__('kit::admin.select_section_type_first'))->columnSpanFull()->visible(fn (callable $get) => empty($get('type'))),
                        ]),
                        RightGrid::make()->schema([
                            Section::make()
                                ->schema([
                                    TextInput::make('title')->required(),
                                    Select::make('type')
                                        ->label(__('kit::admin.section_type'))
                                        ->options($service->getBlocksTypes())
                                        ->required()
                                        ->reactive()
                                        ->disabledOn('edit')
                                        ->afterStateUpdated(fn ($state, callable $set) => $set('data', [])),
                                    Toggle::make('status')->default(true),
                                ]),
                        ]),
                    ]),
            ])->columns(1);
    }

    protected static function buildBlockLanguageSchema(BlockService $service): array
    {
        $languages = app('lang')->adminLanguages();
        $visibleCondition = fn (callable $get) => filled($get('type'));

        if ($languages->count() <= 1) {
            $lang = $languages->first();

            return [
                Section::make()
                    ->schema(function (Get $get) use ($service, $lang) {
                        return $service->getBlockSchema($get('type'), $lang->slug);
                    })
                    ->visible($visibleCondition),
            ];
        }

        return [
            Tabs::make(__('kit::admin.block_data'))->schema($languages->map(function (Language $lang) use ($service) {
                return Tab::make($lang->name)->schema(function (Get $get) use ($service, $lang) {
                    return $service->getBlockSchema($get('type'), $lang->slug);
                });
            })->toArray())
                ->visible($visibleCondition),
        ];
    }
}
