<div>
    @php
        $results = $getState();
    @endphp

    @if($results && count($results) > 0)
        <div class="grid grid-cols-3 gap-3 mt-3">
            @foreach($results as $photo)
                <div
                    wire:click="$set('selected_source', 'unsplash'); $set('unsplash_selected', @js([
                        'source' => $photo['urls']['regular'],
                        'width' => $photo['width'],
                        'height' => $photo['height'],
                        'alt' => [app()->getLocale() => $photo['alt_description'] ?? $photo['description'] ?? ''],
                    ]))"
                    class="relative aspect-square cursor-pointer rounded-lg overflow-hidden hover:ring-2 hover:ring-primary-600 transition-all"
                >
                    <img
                        src="{{ $photo['urls']['small'] }}"
                        alt="{{ $photo['alt_description'] ?? '' }}"
                        class="w-full h-full object-cover"
                    />
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            {{ __('kit::admin.no_images_found') }}
        </div>
    @endif
</div>
