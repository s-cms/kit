<?php

namespace SmartCms\Kit\Actions\Support;

use Lorisleiva\Actions\Concerns\AsAction;
use SmartCms\Kit\VariableTypes\AddressType;
use SmartCms\Kit\VariableTypes\EmailsType;
use SmartCms\Kit\VariableTypes\EmailType;
use SmartCms\Kit\VariableTypes\FileType;
use SmartCms\Kit\VariableTypes\FormType;
use SmartCms\Kit\VariableTypes\HeadingType;
use SmartCms\Kit\VariableTypes\IconType;
use SmartCms\Kit\VariableTypes\ImageType;
use SmartCms\Kit\VariableTypes\LatestCategories;
use SmartCms\Kit\VariableTypes\LatestItems;
use SmartCms\Kit\VariableTypes\LinkType;
use SmartCms\Kit\VariableTypes\MenuType;
use SmartCms\Kit\VariableTypes\PhonesType;
use SmartCms\Kit\VariableTypes\PhoneType;
use SmartCms\Kit\VariableTypes\PopularCategories;
use SmartCms\Kit\VariableTypes\PopularItems;
use SmartCms\Kit\VariableTypes\RandomCategories;
use SmartCms\Kit\VariableTypes\RandomItems;
use SmartCms\Kit\VariableTypes\SocialsType;
use SmartCms\Kit\VariableTypes\StringType;
use SmartCms\Kit\VariableTypes\KeyValueType;
use SmartCms\TemplateBuilder\Support\VariableTypeRegistry;

class RegisterVariableTypes
{
    use AsAction;

    public function __construct(private VariableTypeRegistry $registry) {}

    public function handle(): void
    {
        $this->registry->register(PhoneType::class);
        $this->registry->register(PhonesType::class);
        $this->registry->register(EmailType::class);
        $this->registry->register(EmailsType::class);
        $this->registry->register(AddressType::class);
        $this->registry->register(SocialsType::class);
        $this->registry->register(HeadingType::class);
        $this->registry->register(MenuType::class);
        $this->registry->register(PopularCategories::class);
        $this->registry->register(RandomCategories::class);
        $this->registry->register(PopularItems::class);
        $this->registry->register(RandomItems::class);
        $this->registry->register(LatestCategories::class);
        $this->registry->register(LatestItems::class);
        $this->registry->register(LinkType::class);
        $this->registry->register(IconType::class);
        $this->registry->register(ImageType::class);
        $this->registry->register(FileType::class);
        $this->registry->register(FormType::class);
        $this->registry->register(StringType::class);
        $this->registry->register(KeyValueType::class);
    }
}
