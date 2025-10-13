<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Model Augmentations
    |--------------------------------------------------------------------------
    |
    | Register augmentation classes for each model. Augmentations extend model
    | functionality without modifying core classes.
    |
    | Each augmentation can provide:
    | - Admin form fields (ModifiesFormSchema)
    | - Table columns/filters/actions (ModifiesTableColumns, ModifiesTableFilters, etc.)
    | - Model relationships/casts (ModifiesModelRelations, ModifiesModelCasts)
    | - Relation managers (ModifiesRelationManagers)
    | - Frontend transformations (transform method)
    |
    | Only include the traits you need - no empty methods required!
    |
    */

    'page' => [
        // Register Page augmentations here
        // \App\Augmentations\AuthorAugmentation::class,
        // \App\Augmentations\GalleryAugmentation::class,
        // \App\Augmentations\SeoAugmentation::class,
    ],

    // Add other models as needed
    // 'post' => [],
    // 'product' => [],
];
