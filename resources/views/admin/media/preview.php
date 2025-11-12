<div>
    @if($getRecord())
        <div class="flex items-center justify-center w-full p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
            <img
                src="{{ $getRecord()->getUrl('preview') }}"
                alt="{{ $getRecord()->name }}"
                class="max-w-full max-h-96 object-contain rounded-lg shadow-sm"
            />
        </div>
    @endif
</div>
