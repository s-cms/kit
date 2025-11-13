@php
    $record = $getRecord();
@endphp

@if($record && $record->isImage())
    <div class="rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden bg-gray-50 dark:bg-gray-800 p-4">
        <img
            src="{{ $record->getUrl() }}"
            alt="{{ $record->name }}"
            class="w-full h-auto max-h-96 object-contain mx-auto"
        />
    </div>

    <div class="mt-4 space-y-2 text-sm text-gray-600 dark:text-gray-400">
        <div class="grid grid-cols-2 gap-2">
            <div>
                <strong>{{ __('kit::admin.file_name') }}:</strong> {{ $record->file_name }}
            </div>
            <div>
                <strong>{{ __('kit::admin.mime_type') }}:</strong> {{ $record->mime_type }}
            </div>
            <div>
                <strong>{{ __('kit::admin.dimensions') }}:</strong> {{ $record->width }} × {{ $record->height }}
            </div>
            <div>
                <strong>{{ __('kit::admin.file_size') }}:</strong> {{ format_bytes($record->size) }}
            </div>
        </div>
    </div>
@else
    <div class="rounded-lg border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-800 p-8 text-center">
        <div class="text-gray-500 dark:text-gray-400">
            @if($record)
                <div class="text-6xl mb-4">
                    <x-filament::icon
                        icon="heroicon-o-document"
                        class="h-16 w-16 mx-auto"
                    />
                </div>
                <div class="font-medium">{{ $record->name }}</div>
                <div class="text-sm mt-2">{{ $record->mime_type }}</div>
            @else
                <div>{{ __('kit::admin.no_media_selected') }}</div>
            @endif
        </div>
    </div>
@endif
