<div>
    @if($getState())
        <div class="mt-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <img
                src="{{ $getState()['source'] ?? '' }}"
                alt="{{ $getState()['alt'][app()->getLocale()] ?? '' }}"
                class="max-w-full h-auto rounded"
            />
        </div>
    @endif
</div>
