<div>
    @if($getRecord())
        <div class="space-y-3">
            @foreach(config('kit.media.conversions', []) as $conversionName => $conversionSettings)
                @php
                    $hasConversion = $getRecord()->hasGeneratedConversion($conversionName);
                @endphp
                <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
                    <div class="flex items-center gap-3">
                        @if($hasConversion)
                            <div class="w-12 h-12 overflow-hidden rounded border border-gray-200 dark:border-gray-700">
                                <img
                                    src="{{ $getRecord()->getUrl($conversionName) }}"
                                    alt="{{ $conversionName }}"
                                    class="w-full h-full object-cover"
                                />
                            </div>
                        @else
                            <div class="w-12 h-12 flex items-center justify-center bg-gray-200 dark:bg-gray-700 rounded">
                                <x-filament::icon
                                    icon="heroicon-o-photo"
                                    class="w-6 h-6 text-gray-400"
                                />
                            </div>
                        @endif
                        <div>
                            <div class="font-medium text-sm">{{ ucfirst($conversionName) }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $conversionSettings['width'] }}×{{ $conversionSettings['height'] }}
                                @if($conversionSettings['format'])
                                    • {{ strtoupper($conversionSettings['format']) }}
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($hasConversion)
                        <x-filament::badge color="success">
                            {{ __('kit::admin.generated') }}
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="gray">
                            {{ __('kit::admin.pending') }}
                        </x-filament::badge>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
