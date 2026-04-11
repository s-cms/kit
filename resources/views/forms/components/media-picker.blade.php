<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $statePath = $getStatePath();
        $preview = $field->getMediaPreview();
        $adminLanguages = app('lang')->adminLanguages();
    @endphp

    <div
        x-data="{
            state: @entangle($statePath),
            showModal: false,
            showEditModal: false,
            editName: '',
            editAlt: @js($preview ? ($preview['alt_translations'] ?? []) : []),
            languages: @js($adminLanguages->map(fn($l) => ['slug' => $l->slug, 'name' => $l->name])->values()),
            saving: false,
            search: '',
            media: [],
            loading: false,
            uploading: false,
            urlInput: '',
            showUrlInput: false,
            preview: @js($preview),

            init() {
                this.$watch('state', (value) => {
                    if (!value) this.preview = null;
                });
            },

            async fetchFromUrl() {
                if (!this.urlInput) return;
                this.uploading = true;
                try {
                    const response = await fetch('{{ route("admin.media-picker.fetch-url") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ url: this.urlInput })
                    });
                    const data = await response.json();
                    if (data.success && data.image?.media_id) {
                        this.state = data.image.media_id;
                        this.preview = {
                            id: data.image.media_id,
                            name: this.urlInput.split('/').pop(),
                            url: data.image.source,
                        };
                        this.urlInput = '';
                        this.showUrlInput = false;
                        this.showModal = false;
                    }
                } catch(e) {
                    console.error('URL fetch failed', e);
                }
                this.uploading = false;
            },

            async loadMedia() {
                this.loading = true;
                try {
                    const url = new URL('{{ route("admin.media-picker.library") }}', window.location.origin);
                    if (this.search) url.searchParams.set('search', this.search);
                    const response = await fetch(url, {
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.media = data.media;
                    }
                } catch(e) {
                    console.error('Failed to load media', e);
                }
                this.loading = false;
            },

            selectMedia(item) {
                this.state = item.id;
                this.preview = item;
                this.showModal = false;
            },

            removeMedia() {
                this.state = null;
                this.preview = null;
            },

            openModal() {
                this.showModal = true;
                this.loadMedia();
            },

            openEditModal() {
                if (!this.preview) return;
                this.editName = this.preview.name || '';
                this.editAlt = this.preview.alt_translations || {};
                this.showEditModal = true;
            },

            async saveEdit() {
                if (!this.state) return;
                this.saving = true;
                try {
                    const response = await fetch('{{ route("admin.media-picker.update") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            id: this.state,
                            name: this.editName,
                            alt: this.editAlt,
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.preview.name = this.editName;
                        this.preview.alt_translations = this.editAlt;
                        this.showEditModal = false;
                    }
                } catch(e) {
                    console.error('[MediaPicker] Save failed', e);
                }
                this.saving = false;
            },

            async uploadFile(event) {
                const file = event.target.files[0];
                if (!file) return;

                this.uploading = true;
                const formData = new FormData();
                formData.append('file', file);

                try {
                    const response = await fetch('{{ route("admin.media-picker.upload") }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || '{{ csrf_token() }}'
                        },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success && data.image?.media_id) {
                        this.state = data.image.media_id;
                        this.preview = {
                            id: data.image.media_id,
                            name: file.name,
                            url: data.image.source,
                        };
                        this.showModal = false;
                    }
                } catch(e) {
                    console.error('Upload failed', e);
                }
                this.uploading = false;
                event.target.value = '';
            }
        }"
    >
        {{-- Preview State --}}
        <template x-if="preview">
            <div>
                <div style="position: relative; border-radius: 8px; overflow: hidden; border: 1px solid #e5e7eb; background: #f9fafb;">
                    <img
                        :src="preview.url"
                        :alt="preview.name || ''"
                        style="width: 100%; max-height: 256px; object-fit: contain; display: block;"
                    />
                    <div
                        style="position: absolute; inset: 0; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; gap: 8px; opacity: 0; transition: opacity 0.2s;"
                        onmouseenter="this.style.opacity=1"
                        onmouseleave="this.style.opacity=0"
                    >
                        <button type="button" @click="openEditModal()" title="{{ __('kit::admin.edit') }}"
                            style="padding: 8px; background: white; color: #1f2937; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </button>
                        <button type="button" @click="openModal()" title="{{ __('kit::admin.change') }}"
                            style="padding: 8px; background: white; color: #1f2937; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182" />
                            </svg>
                        </button>
                        <button type="button" @click="removeMedia()" title="{{ __('kit::admin.delete') }}"
                            style="padding: 8px; background: #ef4444; color: white; border-radius: 8px; border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                            <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        {{-- Empty State --}}
        <template x-if="!preview">
            <div>
                <button
                    type="button"
                    @click="openModal()"
                    style="width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px; border: 2px dashed #d1d5db; border-radius: 8px; background: transparent; cursor: pointer; transition: border-color 0.2s;"
                    onmouseenter="this.style.borderColor='var(--primary-500, #3b82f6)'"
                    onmouseleave="this.style.borderColor='#d1d5db'"
                >
                    <svg style="width: 32px; height: 32px; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                    <span style="font-size: 14px; font-weight: 500; color: #6b7280;">{{ __('kit::admin.add_image') }}</span>
                </button>
            </div>
        </template>

        {{-- Modal --}}
        <div
            x-show="showModal"
            @keydown.escape.window="showModal = false"
            style="display: none; position: fixed; inset: 0; z-index: 999; padding: 16px;"
        >
            {{-- Backdrop --}}
            <div @click="showModal = false" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);"></div>

            {{-- Modal Content --}}
            <div style="position: relative; margin: auto; margin-top: 5vh; background: white; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; max-width: 900px; max-height: 80vh; display: flex; flex-direction: column; overflow: hidden;">
                {{-- Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">{{ __('kit::admin.media_library') }}</h3>
                    <button type="button" @click="showModal = false" style="background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px;">
                        <svg style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Toolbar --}}
                <div style="padding: 12px 24px; border-bottom: 1px solid #f3f4f6; display: flex; flex-direction: column; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="flex: 1; position: relative;">
                            <svg style="width: 16px; height: 16px; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input
                                type="text"
                                x-model.debounce.300ms="search"
                                @input.debounce.300ms="loadMedia()"
                                placeholder="{{ __('kit::admin.search') }}..."
                                style="width: 100%; padding: 8px 12px 8px 36px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #111827; outline: none;"
                            />
                        </div>
                        <label
                            :style="'display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 14px; font-weight: 500; color: white; background: #2563eb; border: 1px solid #2563eb; border-radius: 8px; cursor: pointer; white-space: nowrap;' + (uploading ? ' opacity: 0.5; pointer-events: none;' : '')"
                        >
                            <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                            </svg>
                            <span x-show="!uploading">{{ __('kit::admin.upload') }}</span>
                            <span x-show="uploading" style="display: none;">{{ __('kit::admin.uploading') }}...</span>
                            <input type="file" accept="image/*" style="display: none;" @change="uploadFile($event)" :disabled="uploading" />
                        </label>
                        <button
                            type="button"
                            @click="showUrlInput = !showUrlInput"
                            style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; font-size: 14px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer; white-space: nowrap;"
                        >
                            <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                            </svg>
                            {{ __('kit::admin.import_from_url') }}
                        </button>
                    </div>
                    <div x-show="showUrlInput" :style="showUrlInput ? 'display: flex; align-items: center; gap: 8px; width: 100%;' : 'display: none;'">
                        <input
                            type="url"
                            x-model="urlInput"
                            @keydown.enter.prevent="fetchFromUrl()"
                            placeholder="https://example.com/image.jpg"
                            style="flex: 1; padding: 10px 14px; font-size: 14px; color: #111827; background: white; border: 1px solid #d1d5db; border-radius: 8px; outline: none;"
                            onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'"
                            onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'"
                        />
                        <button
                            type="button"
                            @click="fetchFromUrl()"
                            :style="'padding: 10px 18px; font-size: 14px; font-weight: 500; color: white; background: #2563eb; border: 1px solid #2563eb; border-radius: 8px; cursor: pointer; white-space: nowrap;' + (uploading ? ' opacity: 0.5; pointer-events: none;' : '')"
                        >
                            <span x-show="!uploading">{{ __('kit::admin.fetch_image') }}</span>
                            <span x-show="uploading" style="display: none;">{{ __('kit::admin.uploading') }}...</span>
                        </button>
                    </div>
                </div>

                {{-- Grid --}}
                <div style="flex: 1; overflow-y: auto; padding: 24px;">
                    {{-- Loading --}}
                    <div x-show="loading" style="align-items: center; justify-content: center; padding: 48px 0;" :style="loading ? 'display: flex' : 'display: none'"
                    >
                        <svg style="width: 32px; height: 32px; color: var(--primary-500, #3b82f6); animation: spin 1s linear infinite;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle style="opacity: 0.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path style="opacity: 0.75;" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    {{-- Empty --}}
                    <template x-if="!loading && media.length === 0">
                        <div style="text-align: center; padding: 48px 0; color: #6b7280;">
                            {{ __('kit::admin.no_images_found') }}
                        </div>
                    </template>

                    {{-- Media Grid --}}
                    <template x-if="!loading && media.length > 0">
                        <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 12px;">
                            <template x-for="item in media" :key="item.id">
                                <div
                                    @click="selectMedia(item)"
                                    style="position: relative; aspect-ratio: 1; cursor: pointer; border-radius: 8px; overflow: hidden; border: 2px solid transparent; transition: border-color 0.2s; background: #f3f4f6;"
                                    onmouseenter="this.style.borderColor='var(--primary-500, #3b82f6)'"
                                    onmouseleave="this.style.borderColor='transparent'"
                                >
                                    <img
                                        :src="item.url"
                                        :alt="item.name"
                                        style="width: 100%; height: 100%; object-fit: cover;"
                                        loading="lazy"
                                    />
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div
            x-show="showEditModal"
            @keydown.escape.window="showEditModal = false"
            style="display: none; position: fixed; inset: 0; z-index: 999; padding: 16px;"
        >
            <div @click="showEditModal = false" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);"></div>

            <div style="position: relative; margin: auto; margin-top: 15vh; background: white; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; max-width: 480px; overflow: hidden;">
                {{-- Header --}}
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
                    <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">{{ __('kit::admin.edit') }}</h3>
                    <button type="button" @click="showEditModal = false" style="background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px;">
                        <svg style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Form --}}
                <div style="padding: 24px; display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">{{ __('kit::admin.name') }}</label>
                        <input
                            type="text"
                            x-model="editName"
                            style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #111827; outline: none; box-sizing: border-box;"
                        />
                    </div>
                    <template x-for="lang in languages" :key="lang.slug">
                        <div>
                            <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">
                                <span>{{ __('kit::admin.alt_text') }}</span>
                                <template x-if="languages.length > 1">
                                    <span style="font-weight: 400; color: #6b7280;" x-text="'(' + lang.name + ')'"></span>
                                </template>
                            </label>
                            <input
                                type="text"
                                x-model="editAlt[lang.slug]"
                                style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #111827; outline: none; box-sizing: border-box;"
                            />
                        </div>
                    </template>
                    <p style="font-size: 12px; color: #6b7280; margin-top: -8px;">{{ __('kit::admin.alt_text_help') }}</p>
                </div>

                {{-- Footer --}}
                <div style="display: flex; justify-content: flex-end; gap: 8px; padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                    <button
                        type="button"
                        @click="showEditModal = false"
                        style="padding: 8px 16px; font-size: 14px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;"
                    >
                        {{ __('kit::admin.cancel') }}
                    </button>
                    <button
                        type="button"
                        @click="saveEdit()"
                        :style="saving ? 'opacity: 0.5; pointer-events: none;' : ''"
                        style="padding: 8px 16px; font-size: 14px; font-weight: 500; color: white; background: var(--primary-600, #2563eb); border: none; border-radius: 8px; cursor: pointer;"
                    >
                        <span x-show="!saving">{{ __('kit::admin.save') }}</span>
                        <span x-show="saving" style="display: none;">...</span>
                    </button>
                </div>
            </div>
        </div>

        <style>
            @keyframes spin { to { transform: rotate(360deg); } }
        </style>
    </div>
</x-dynamic-component>
