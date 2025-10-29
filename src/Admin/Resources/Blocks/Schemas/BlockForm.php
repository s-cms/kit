<?php

namespace SmartCms\Kit\Admin\Resources\Blocks\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use SmartCms\Kit\Services\Block\BlockService;
use SmartCms\Lang\Models\Language;

class BlockForm
{
    public static function configure(Schema $schema): Schema
    {
        $service = app(BlockService::class);

        return $schema
            ->components([
                Section::make('General')
                    ->columns(3)
                    ->schema([
                        TextInput::make('title')->required(),
                        Select::make('type')
                            ->label('Section Type')
                            ->options($service->getBlocksTypes())
                            ->required()
                            ->reactive()
                            ->disabledOn('edit')
                            ->afterStateUpdated(fn ($state, callable $set) => $set('data', [])),
                        Toggle::make('status')->inline(false)->default(true),
                    ]),
                Tabs::make('Block Data')->schema(app('lang')->adminLanguages()->map(function (Language $lang) use ($service) {
                    return Tab::make($lang->name)->schema(function (Get $get) use ($service, $lang) {
                        return $service->getBlockSchema($get('type'), $lang->slug);
                    });
                })->toArray())
                    ->visible(fn (callable $get) => filled($get('type'))),
            ])->columns(1);
    }
}
