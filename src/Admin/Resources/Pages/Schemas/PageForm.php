<?php

namespace SmartCms\Kit\Admin\Resources\Pages\Schemas;

use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use SmartCms\Kit\Admin\Forms\PageNameField;
use SmartCms\Kit\Admin\Forms\PageSlugField;
use SmartCms\Kit\Models\Page;
use SmartCms\Seo\Admin\Seos\Schemas\RelatedSeoForm;
use SmartCms\Support\Admin\Components\Layout\FormGrid;
use SmartCms\Support\Admin\Components\Layout\LeftGrid;
use SmartCms\Support\Admin\Components\Layout\RightGrid;

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
                            ]),
                            ...RelatedSeoForm::configure($schema)->getComponents(),
                            // Add augmented schema from augmentations
                            ...Page::getAugmentedSchema(),
                        ]),
                        RightGrid::make()->schema(PageSummary::make()),
                    ]),
                ]
            )->columns(1);
    }
}
