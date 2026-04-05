<?php

use SmartCms\Kit\Models\Tag;

return [
    /*
     * The given function generates a URL friendly "slug" from the tag name property before saving it.
     */
    'slugger' => null,

    /*
     * The fully qualified class name of the tag model.
     */
    'tag_model' => Tag::class,

    /*
     * The name of the table to use for the tags.
     */
    'table_names' => [
        'tags' => 'tags',
        'taggables' => 'taggables',
    ],
];
