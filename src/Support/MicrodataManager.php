<?php

namespace SmartCms\Kit\Support;

use SmartCms\Kit\Actions\Microdata\OrganizationMicrodata;
use SmartCms\Kit\Actions\Microdata\WebsiteMicrodata;

class MicrodataManager
{
    public array $microdata = [];

    public function __construct()
    {
        $this->microdata = [
            OrganizationMicrodata::run(),
            WebsiteMicrodata::run(),
        ];
    }

    public function add(array $microdata): self
    {
        $this->microdata[] = $microdata;

        return $this;
    }

    public function get(): array
    {
        return $this->microdata;
    }

    public function render(): string
    {
        return collect($this->microdata)->map(fn ($microdata): string => '<script type="application/ld+json">' . json_encode($microdata) . '</script>')->implode("\n");
    }
}
