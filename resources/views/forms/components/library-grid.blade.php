<div>
    @php
        $results = $getState();
    @endphp

    @if($results && count($results) > 0)
        <div class="grid grid-cols-4 gap-2 mt-3 max-h-96 overflow-y-auto">
            @foreach($results as $media)
                <div
                    wire:click="$set('library_selected_uuid', '{{ $media['uuid'] }}')"
                    class="relative aspect-square cursor-pointer rounded-lg overflow-hidden hover:ring-2 hover:ring-primary-600 transition-all"
                >
                    <img
                        src="{{ $media['thumb_url'] }}"
                        alt="{{ $media['name'] }}"
                        class="w-full h-full object-cover"
                    />
                    <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white text-xs p-1 truncate">
                        {{ $media['name'] }}
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="text-center py-8 text-gray-500">
            {{ __('kit::admin.no_images_found') }}
        </div>
    @endif
</div>
