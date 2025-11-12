<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="mediaPicker({
        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
        collection: '{{ $getCollection() }}',
        unsplashEnabled: {{ $isUnsplashEnabled() ? 'true' : 'false' }},
        unsplashAccessKey: '{{ config('kit.unsplash.access_key') }}'
    })" class="space-y-4">

        {{-- Current Image Preview --}}
        <div x-show="state && state.source" class="relative">
            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
                <img
                    x-bind:src="state?.source"
                    x-bind:alt="state?.alt?.[currentLang()] || ''"
                    class="w-24 h-24 object-cover rounded"
                />
                <div class="flex-1">
                    <div class="text-sm font-medium" x-text="state?.alt?.[currentLang()] || state?.file_name || 'Image'"></div>
                    <div class="text-xs text-gray-500" x-show="state?.width && state?.height">
                        <span x-text="state?.width"></span> × <span x-text="state?.height"></span>
                    </div>
                </div>
                <button
                    type="button"
                    @click="state = null"
                    class="text-danger-600 hover:text-danger-700"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        {{-- Upload Tabs --}}
        <div x-show="!state || !state.source" class="border border-gray-200 dark:border-gray-700 rounded-lg">
            <div class="flex border-b border-gray-200 dark:border-gray-700" role="tablist">
                @if($isUploadEnabled())
                <button
                    type="button"
                    @click="activeTab = 'upload'"
                    x-bind:class="activeTab === 'upload' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-2 border-b-2 font-medium text-sm"
                >
                    {{ __('kit::admin.upload_image') }}
                </button>
                @endif

                @if($isUrlEnabled())
                <button
                    type="button"
                    @click="activeTab = 'url'"
                    x-bind:class="activeTab === 'url' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-2 border-b-2 font-medium text-sm"
                >
                    {{ __('kit::admin.import_from_url') }}
                </button>
                @endif

                @if($isUnsplashEnabled())
                <button
                    type="button"
                    @click="activeTab = 'unsplash'"
                    x-bind:class="activeTab === 'unsplash' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-2 border-b-2 font-medium text-sm"
                >
                    {{ __('kit::admin.browse_unsplash') }}
                </button>
                @endif

                @if($isLibraryEnabled())
                <button
                    type="button"
                    @click="activeTab = 'library'"
                    x-bind:class="activeTab === 'library' ? 'border-primary-600 text-primary-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-2 border-b-2 font-medium text-sm"
                >
                    {{ __('kit::admin.browse_library') }}
                </button>
                @endif
            </div>

            {{-- Tab Contents --}}
            <div class="p-4">
                {{-- Upload Tab --}}
                @if($isUploadEnabled())
                <div x-show="activeTab === 'upload'" x-cloak>
                    <input
                        type="file"
                        @change="handleFileUpload($event)"
                        accept="image/*"
                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-primary-50 file:text-primary-700 hover:file:bg-primary-100"
                    />
                </div>
                @endif

                {{-- URL Import Tab --}}
                @if($isUrlEnabled())
                <div x-show="activeTab === 'url'" x-cloak>
                    <div class="space-y-3">
                        <input
                            type="url"
                            x-model="urlInput"
                            placeholder="https://example.com/image.jpg"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                        />
                        <button
                            type="button"
                            @click="fetchFromUrl()"
                            x-bind:disabled="!urlInput || fetching"
                            class="w-full px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 disabled:opacity-50 disabled:cursor-not-allowed"
                        >
                            <span x-show="!fetching">{{ __('kit::admin.fetch_image') }}</span>
                            <span x-show="fetching">{{ __('kit::admin.fetching') }}...</span>
                        </button>
                    </div>
                </div>
                @endif

                {{-- Unsplash Tab --}}
                @if($isUnsplashEnabled())
                <div x-show="activeTab === 'unsplash'" x-cloak>
                    <div class="space-y-3">
                        <div class="flex gap-2">
                            <input
                                type="text"
                                x-model="unsplashQuery"
                                @keydown.enter.prevent="searchUnsplash()"
                                placeholder="{{ __('kit::admin.search_unsplash') }}"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                            />
                            <button
                                type="button"
                                @click="searchUnsplash()"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700"
                            >
                                {{ __('kit::admin.search') }}
                            </button>
                        </div>

                        <div x-show="unsplashLoading" class="text-center py-8">
                            <div class="animate-spin inline-block w-8 h-8 border-4 border-current border-t-transparent text-primary-600 rounded-full"></div>
                        </div>

                        <div x-show="!unsplashLoading && unsplashResults.length > 0" class="grid grid-cols-3 gap-2">
                            <template x-for="photo in unsplashResults" :key="photo.id">
                                <div
                                    @click="selectUnsplashPhoto(photo)"
                                    class="relative aspect-square cursor-pointer rounded-lg overflow-hidden hover:ring-2 hover:ring-primary-600"
                                >
                                    <img
                                        x-bind:src="photo.urls.small"
                                        x-bind:alt="photo.alt_description"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                            </template>
                        </div>

                        <div x-show="!unsplashLoading && unsplashResults.length === 0 && unsplashSearched" class="text-center py-8 text-gray-500">
                            {{ __('kit::admin.no_images_found') }}
                        </div>
                    </div>
                </div>
                @elseif($isLibraryEnabled() && !$isUnsplashEnabled())
                <div x-show="activeTab === 'unsplash'" x-cloak class="text-center py-8 text-gray-500">
                    {{ __('kit::admin.unsplash_not_configured') }}
                </div>
                @endif

                {{-- Library Browser Tab --}}
                @if($isLibraryEnabled())
                <div x-show="activeTab === 'library'" x-cloak>
                    <div class="space-y-3">
                        <input
                            type="text"
                            x-model="librarySearch"
                            @input.debounce.300ms="searchLibrary()"
                            placeholder="{{ __('kit::admin.search') }}..."
                            class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800"
                        />

                        <div x-show="libraryLoading" class="text-center py-8">
                            <div class="animate-spin inline-block w-8 h-8 border-4 border-current border-t-transparent text-primary-600 rounded-full"></div>
                        </div>

                        <div x-show="!libraryLoading && libraryResults.length > 0" class="grid grid-cols-4 gap-2 max-h-96 overflow-y-auto">
                            <template x-for="media in libraryResults" :key="media.id">
                                <div
                                    @click="selectLibraryMedia(media)"
                                    class="relative aspect-square cursor-pointer rounded-lg overflow-hidden hover:ring-2 hover:ring-primary-600"
                                >
                                    <img
                                        x-bind:src="media.thumb_url"
                                        x-bind:alt="media.name"
                                        class="w-full h-full object-cover"
                                    />
                                </div>
                            </template>
                        </div>

                        <div x-show="!libraryLoading && libraryResults.length === 0" class="text-center py-8 text-gray-500">
                            {{ __('kit::admin.no_images_found') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @once
    @push('scripts')
    <script>
        function mediaPicker(config) {
            return {
                state: config.state,
                collection: config.collection,
                activeTab: 'upload',
                urlInput: '',
                fetching: false,
                unsplashQuery: '',
                unsplashResults: [],
                unsplashLoading: false,
                unsplashSearched: false,
                librarySearch: '',
                libraryResults: [],
                libraryLoading: false,

                init() {
                    this.searchLibrary();
                },

                currentLang() {
                    return document.documentElement.lang || 'en';
                },

                async handleFileUpload(event) {
                    const file = event.target.files[0];
                    if (!file) return;

                    const formData = new FormData();
                    formData.append('file', file);
                    formData.append('collection', this.collection);

                    try {
                        const response = await fetch('/admin/media-picker/upload', {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.state = data.image;
                        }
                    } catch (error) {
                        console.error('Upload failed:', error);
                    }
                },

                async fetchFromUrl() {
                    if (!this.urlInput) return;

                    this.fetching = true;
                    try {
                        const response = await fetch('/admin/media-picker/fetch-url', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                url: this.urlInput,
                                collection: this.collection
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.state = data.image;
                            this.urlInput = '';
                        }
                    } catch (error) {
                        console.error('Fetch failed:', error);
                    } finally {
                        this.fetching = false;
                    }
                },

                async searchUnsplash() {
                    if (!this.unsplashQuery) return;

                    this.unsplashLoading = true;
                    this.unsplashSearched = true;

                    try {
                        const response = await fetch(`/admin/media-picker/unsplash/search?query=${encodeURIComponent(this.unsplashQuery)}`, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();
                        this.unsplashResults = data.results || [];
                    } catch (error) {
                        console.error('Unsplash search failed:', error);
                    } finally {
                        this.unsplashLoading = false;
                    }
                },

                async selectUnsplashPhoto(photo) {
                    try {
                        const response = await fetch('/admin/media-picker/unsplash/download', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({
                                photo: photo,
                                collection: this.collection
                            })
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.state = data.image;
                        }
                    } catch (error) {
                        console.error('Download failed:', error);
                    }
                },

                async searchLibrary() {
                    this.libraryLoading = true;

                    try {
                        const url = new URL('/admin/media-picker/library', window.location.origin);
                        if (this.librarySearch) {
                            url.searchParams.append('search', this.librarySearch);
                        }

                        const response = await fetch(url, {
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            }
                        });

                        const data = await response.json();
                        this.libraryResults = data.media || [];
                    } catch (error) {
                        console.error('Library search failed:', error);
                    } finally {
                        this.libraryLoading = false;
                    }
                },

                selectLibraryMedia(media) {
                    this.state = media.image;
                }
            };
        }
    </script>
    @endpush
    @endonce
</x-dynamic-component>
