<?php

namespace SmartCms\Kit\Actions\Admin;

use Lorisleiva\Actions\Concerns\AsAction;
use SmartCms\Kit\Admin\Resources\Pages\Pages\ListPages;
use SmartCms\Kit\Models\Page;

class GetPageListUrl
{
    use AsAction;

    public function handle(Page $record): string
    {
        $url = ListPages::getUrl();

        return $url;
    }
}
