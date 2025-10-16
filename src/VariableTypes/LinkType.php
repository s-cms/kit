<?php

namespace SmartCms\Kit\VariableTypes;

use CodeWithDennis\FilamentLucideIcons\Enums\LucideIcon;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Flex;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use SmartCms\Kit\Admin\Components\IconInput;
use SmartCms\Menu\MenuRegistry;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class LinkType implements VariableTypeInterface
{
    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'link';
    }

    public function getDefaultValue(): mixed
    {
        return [
            'title' => 'Default link',
            'type' => 'link',
            'is_external' => false,
            'icon' => LucideIcon::Image->value,
            'url' => url('/'),
        ];
    }

    public function getSchema(string $name): Field | Component
    {
        return Group::make([
            // FusedGroup::make([
            Select::make($name . '.type')
                ->label(__('menu::admin.type'))
                ->options(app(MenuRegistry::class)->all())
                ->reactive()
                ->formatStateUsing(function ($state, Select $component) {
                    if ($state) {
                        return $state;
                    }
                    $options = $component->getOptions();
                    if (count($options) > 0) {
                        return array_key_first($options);
                    }
                })
                ->required(),
            Flex::make(function (Get $get) use ($name): array {
                $type = $get($name . '.type');
                if (! $type) {
                    return [];
                }
                $component = app(MenuRegistry::class)->getSchemaByType($type);

                return [$component->statePath($name . '.' . $component->getName())];
            }),
            Grid::make(5)->schema([
                TextInput::make($name . '.title')->label(__('kit::admin.title'))->placeholder(__('kit::admin.title'))->columnSpan(2),
                IconInput::make($name . '.icon')->placeholder(__('kit::admin.icon'))->hint('')->columnSpan(2),
                Toggle::make($name . '.is_external')->label(__('kit::admin.open_url_in_new_tab'))->inline(false),

            ])->columnSpanFull(),
            // ])->columns(2)->columnSpanFull(),
        ])->columns(2);
    }

    public function getValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $this->getDefaultValue();
        }
        $value['url'] = app(MenuRegistry::class)->getLinkByType($value);
        if (!isset($value['icon'])) {
            $value['icon'] = LucideIcon::Image->value;
        }
        return $value;
    }
}
