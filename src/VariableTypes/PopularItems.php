<?php

namespace SmartCms\Kit\VariableTypes;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Group;
use SmartCms\Kit\Models\Front\FrontPage;
use SmartCms\Kit\Models\Page;
use SmartCms\TemplateBuilder\Support\VariableTypeInterface;

class PopularItems implements VariableTypeInterface
{
    public const int DEFAULT_LIMIT = 3;

    public static function make(): self
    {
        return new self;
    }

    public static function getName(): string
    {
        return 'popular_items';
    }

    public function getDefaultValue(): mixed
    {
        return FrontPage::query()->limit(self::DEFAULT_LIMIT)->get();
    }

    public function getSchema(string $name): Field | Component
    {
        return Group::make([
            Select::make($name . '.root_id')->options(Page::query()->where('parent_id', null)->where('is_root', true)->pluck('name', 'id'))->required(),
            TextInput::make($name . '.limit')->default(self::DEFAULT_LIMIT)->numeric()->formatStateUsing(fn ($state) => $state ?? self::DEFAULT_LIMIT),
        ]);
    }

    public function getValue(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $this->getDefaultValue();
        }

        return FrontPage::query()->where('root_id', $value['root_id'] ?? 0)->limit($value['limit'] ?? 3)->get();
    }
}
