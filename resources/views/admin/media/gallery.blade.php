<x-filament-panels::page>
    @php
        $folders = $this->getFolders();
        $media = $this->getMedia();
        $breadcrumbParts = $this->getBreadcrumbParts();
        $currentTab = $this->mediaTab;
    @endphp

    <div>
        {{-- Tabs --}}
        <div style="display: flex; gap: 0; margin-bottom: 16px; border-bottom: 2px solid #e5e7eb;">
            @foreach(['images' => __('kit::admin.images'), 'video' => __('kit::admin.video'), 'documents' => __('kit::admin.documents')] as $tabKey => $tabLabel)
                <button type="button" wire:click="switchTab('{{ $tabKey }}')"
                    style="padding: 10px 20px; font-size: 14px; font-weight: 500; border: none; cursor: pointer; background: transparent; border-bottom: 2px solid {{ $currentTab === $tabKey ? 'var(--primary-500, #3b82f6)' : 'transparent' }}; color: {{ $currentTab === $tabKey ? 'var(--primary-600, #2563eb)' : '#6b7280' }}; margin-bottom: -2px; transition: all 0.2s;">
                    {{ $tabLabel }}
                </button>
            @endforeach
        </div>

        {{-- Move Mode Bar --}}
        @if($this->moveMode)
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 12px 16px; background: #dbeafe; border-radius: 8px; border: 1px solid #93c5fd;">
                <svg style="width: 20px; height: 20px; color: #2563eb;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 9.776c.112-.017.227-.026.344-.026h15.812c.117 0 .232.009.344.026m-16.5 0a2.25 2.25 0 0 0-1.883 2.542l.857 6a2.25 2.25 0 0 0 2.227 1.932H19.05a2.25 2.25 0 0 0 2.227-1.932l.857-6a2.25 2.25 0 0 0-1.883-2.542m-16.5 0V6A2.25 2.25 0 0 1 6 3.75h3.879a1.5 1.5 0 0 1 1.06.44l2.122 2.12a1.5 1.5 0 0 0 1.06.44H18A2.25 2.25 0 0 1 20.25 9v.776" />
                </svg>
                <span style="font-size: 14px; font-weight: 500; color: #1e40af;">
                    {{ __('kit::admin.moving_items', ['count' => count($this->selected)]) }}
                </span>
                <span style="flex: 1;"></span>
                <button type="button" wire:click="moveHere"
                    style="padding: 6px 16px; font-size: 13px; font-weight: 500; color: white; background: var(--primary-600, #2563eb); border: none; border-radius: 6px; cursor: pointer;">
                    {{ __('kit::admin.move_here') }}
                </button>
                <button type="button" wire:click="cancelMove"
                    style="padding: 6px 16px; font-size: 13px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
                    {{ __('kit::admin.cancel') }}
                </button>
            </div>
        @endif

        {{-- Bulk Actions Bar --}}
        @if(count($this->selected) > 0 && !$this->moveMode)
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; padding: 12px 16px; background: #fef3c7; border-radius: 8px;">
                <span style="font-size: 14px; font-weight: 500; color: #92400e;">
                    {{ count($this->selected) }} {{ __('kit::admin.selected') }}
                </span>
                <button type="button" wire:click="startMove"
                    style="padding: 6px 12px; font-size: 13px; font-weight: 500; color: #1e40af; background: #dbeafe; border: none; border-radius: 6px; cursor: pointer;">
                    {{ __('kit::admin.move') }}
                </button>
                <button type="button" wire:click="deleteSelected" wire:confirm="{{ __('kit::admin.confirm_delete_selected') }}"
                    style="padding: 6px 12px; font-size: 13px; font-weight: 500; color: white; background: #ef4444; border: none; border-radius: 6px; cursor: pointer;">
                    {{ __('kit::admin.delete') }}
                </button>
                <button type="button" wire:click="$set('selected', [])"
                    style="padding: 6px 12px; font-size: 13px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">
                    {{ __('kit::admin.cancel') }}
                </button>
            </div>
        @endif

        {{-- Toolbar --}}
        @if(!$this->moveMode)
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 16px; flex-wrap: wrap;">
                {{-- Search --}}
                <div style="flex: 1; min-width: 200px; position: relative;">
                    <svg style="width: 16px; height: 16px; position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('kit::admin.search') }}..."
                        style="width: 100%; padding: 8px 12px 8px 36px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #111827; outline: none;" />
                </div>

                {{-- Upload --}}
                <label style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; color: white; background: #2563eb; border: 1px solid #2563eb; border-radius: 8px; cursor: pointer;">
                    <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                    </svg>
                    {{ __('kit::admin.upload') }}
                    <input type="file" accept="{{ \SmartCms\Kit\Admin\Resources\Media\Pages\ListMedia::getAcceptAttribute($currentTab) }}" multiple wire:model="uploadFiles" style="display: none;" />
                </label>

                {{-- Upload from URL (modal trigger) --}}
                <div x-data="{ showUrlModal: false }" style="display: inline-block;">
                    <button type="button" @click="showUrlModal = true"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">
                        <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                        </svg>
                        {{ __('kit::admin.import_from_url') }}
                    </button>

                    {{-- URL Modal --}}
                    <div x-show="showUrlModal" @keydown.escape.window="showUrlModal = false"
                        :style="showUrlModal ? 'display: block; position: fixed; inset: 0; z-index: 999; padding: 16px;' : 'display: none;'">
                        <div @click="showUrlModal = false" style="position: absolute; inset: 0; background: rgba(0,0,0,0.5);"></div>
                        <div style="position: relative; margin: 15vh auto 0; background: white; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; max-width: 520px; overflow: hidden;">
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; border-bottom: 1px solid #e5e7eb;">
                                <h3 style="font-size: 18px; font-weight: 600; color: #111827; margin: 0;">{{ __('kit::admin.import_from_url') }}</h3>
                                <button type="button" @click="showUrlModal = false" style="background: none; border: none; cursor: pointer; color: #9ca3af; padding: 4px;">
                                    <svg style="width: 20px; height: 20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div style="padding: 24px;">
                                <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 6px;">{{ __('kit::admin.image_url') }}</label>
                                <input type="url" wire:model="urlInput"
                                    @keydown.enter.prevent="$wire.uploadFromUrl(); showUrlModal = false"
                                    placeholder="https://example.com/image.jpg"
                                    style="width: 100%; padding: 10px 14px; font-size: 14px; color: #111827; background: white; border: 1px solid #d1d5db; border-radius: 8px; outline: none; box-sizing: border-box;"
                                    onfocus="this.style.borderColor='#2563eb'; this.style.boxShadow='0 0 0 3px rgba(37,99,235,0.1)'"
                                    onblur="this.style.borderColor='#d1d5db'; this.style.boxShadow='none'" />
                            </div>
                            <div style="display: flex; justify-content: flex-end; gap: 8px; padding: 16px 24px; border-top: 1px solid #e5e7eb;">
                                <button type="button" @click="showUrlModal = false"
                                    style="padding: 8px 16px; font-size: 14px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">
                                    {{ __('kit::admin.cancel') }}
                                </button>
                                <button type="button" wire:click="uploadFromUrl" @click="showUrlModal = false"
                                    style="padding: 8px 16px; font-size: 14px; font-weight: 500; color: white; background: #2563eb; border: 1px solid #2563eb; border-radius: 8px; cursor: pointer;">
                                    {{ __('kit::admin.fetch_image') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Process All --}}
                @if($currentTab === 'images' && $this->getUnprocessedCount() > 0)
                    <button type="button" wire:click="processAll" wire:confirm="{{ __('kit::admin.confirm_process_all') }}"
                        style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; color: #92400e; background: #fef3c7; border: 1px solid #fbbf24; border-radius: 8px; cursor: pointer;">
                        <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182M2.985 19.644l3.181-3.182" />
                        </svg>
                        {{ __('kit::admin.process_unprocessed', ['count' => $this->getUnprocessedCount()]) }}
                    </button>
                @endif

                {{-- Scan Disk --}}
                <button type="button" wire:click="scanDisk"
                    style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">
                    <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    {{ __('kit::admin.scan_disk') }}
                </button>

                {{-- New Folder --}}
                <div x-data="{ showInput: false }">
                    <template x-if="!showInput">
                        <button type="button" @click="showInput = true"
                            style="display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; font-size: 14px; font-weight: 500; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 8px; cursor: pointer;">
                            <svg style="width: 16px; height: 16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.5v6m3-3H9m4.06-7.19-2.12-2.12a1.5 1.5 0 0 0-1.061-.44H4.5A2.25 2.25 0 0 0 2.25 6v12a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18V9a2.25 2.25 0 0 0-2.25-2.25h-5.379a1.5 1.5 0 0 1-1.06-.44Z" />
                            </svg>
                            {{ __('kit::admin.new_folder') }}
                        </button>
                    </template>
                    <template x-if="showInput">
                        <div style="display: flex; gap: 4px;">
                            <input type="text" wire:model="newFolderName"
                                @keydown.enter="$wire.createFolder(); showInput = false"
                                @keydown.escape="showInput = false"
                                x-init="$nextTick(() => $el.focus())"
                                placeholder="{{ __('kit::admin.folder_name') }}"
                                style="padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #111827; outline: none; width: 180px;" />
                            <button type="button" wire:click="createFolder" @click="showInput = false"
                                style="padding: 8px 12px; font-size: 14px; color: white; background: var(--primary-600, #2563eb); border: none; border-radius: 8px; cursor: pointer;">
                                {{ __('kit::admin.create') }}
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        @endif

        {{-- Folder Breadcrumbs --}}
        @if($this->currentPath)
            <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 16px; font-size: 14px;">
                <button type="button" wire:click="navigateToFolder('')" style="color: var(--primary-600, #2563eb); background: none; border: none; cursor: pointer; font-size: 14px;">
                    {{ ucfirst($currentTab) }}
                </button>
                @foreach($breadcrumbParts as $part)
                    <span style="color: #9ca3af;">/</span>
                    <button type="button" wire:click="navigateToFolder('{{ $part['path'] }}')" style="color: var(--primary-600, #2563eb); background: none; border: none; cursor: pointer; font-size: 14px;">
                        {{ $part['name'] }}
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Content --}}
        <div>
            {{-- Folders --}}
            @if(count($folders) > 0 || $this->currentPath)
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px; margin-bottom: 24px;">
                    @if($this->currentPath)
                        <div wire:click="navigateUp"
                            style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px 12px; border-radius: 8px; border: 1px solid #e5e7eb; cursor: pointer; transition: border-color 0.2s;"
                            onmouseenter="this.style.borderColor='var(--primary-500, #3b82f6)'"
                            onmouseleave="this.style.borderColor='#e5e7eb'">
                            <svg style="width: 36px; height: 36px; color: #6b7280;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                            </svg>
                            <span style="font-size: 12px; color: #6b7280;">..</span>
                        </div>
                    @endif
                    @foreach($folders as $folder)
                        @php $folderEmpty = $this->isFolderEmpty($folder['path']); @endphp
                        <div wire:click="navigateToFolder('{{ $folder['path'] }}')"
                            style="position: relative; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px; padding: 24px 12px; border-radius: 8px; border: 1px solid #e5e7eb; cursor: pointer; transition: border-color 0.2s;"
                            onmouseenter="this.style.borderColor='var(--primary-500, #3b82f6)'; this.querySelector('.folder-delete').style.opacity=1"
                            onmouseleave="this.style.borderColor='#e5e7eb'; this.querySelector('.folder-delete').style.opacity=0">
                            <svg style="width: 48px; height: 48px; color: #f59e0b;" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19.5 21a3 3 0 0 0 3-3v-4.5a3 3 0 0 0-3-3h-15a3 3 0 0 0-3 3V18a3 3 0 0 0 3 3h15ZM1.5 10.146V6a3 3 0 0 1 3-3h5.379a2.25 2.25 0 0 1 1.59.659l2.122 2.121c.14.141.331.22.53.22H19.5a3 3 0 0 1 3 3v1.146A4.483 4.483 0 0 0 19.5 9h-15a4.483 4.483 0 0 0-3 1.146Z" />
                            </svg>
                            <span style="font-size: 12px; color: #374151; text-align: center; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 100%;">{{ $folder['name'] }}</span>
                            <button type="button" class="folder-delete" @click.stop
                                @if($folderEmpty)
                                    wire:click="deleteFolder('{{ $folder['path'] }}')" wire:confirm="{{ __('kit::admin.confirm_delete_folder') }}"
                                @else
                                    wire:click="deleteFolder('{{ $folder['path'] }}', true)" wire:confirm="{{ __('kit::admin.confirm_delete_folder_not_empty') }}"
                                @endif
                                style="position: absolute; top: 6px; right: 6px; padding: 5px; background: #ef4444; border-radius: 6px; border: none; cursor: pointer; opacity: 0; transition: opacity 0.2s; z-index: 2;">
                                <svg style="width: 14px; height: 14px; color: white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Media Grid --}}
            @if($media->count() > 0)
                <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 12px;"
                     x-data="{
                        editItem: null, editName: '', editAlt: {}, saving: false,
                        languages: @js(app('lang')->adminLanguages()->map(fn($l) => ['slug' => $l->slug, 'name' => $l->name])->values()),
                        openEdit(item) { this.editItem = item; this.editName = item.name || ''; this.editAlt = item.alt || {}; },
                        async saveEdit() {
                            if (!this.editItem) return;
                            this.saving = true;
                            try {
                                const r = await fetch('{{ route("admin.media-picker.update") }}', { method: 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '{{ csrf_token() }}' }, body: JSON.stringify({ id: this.editItem.id, name: this.editName, alt: this.editAlt }) });
                                const d = await r.json();
                                if (d.success) { this.editItem = null; $wire.$refresh(); }
                            } catch(e) { console.error(e); }
                            this.saving = false;
                        }
                     }"
                >
                    @foreach($media as $item)
                        <div style="position: relative; border-radius: 8px; overflow: hidden; border: 2px solid {{ in_array($item->id, $this->selected) ? 'var(--primary-500, #3b82f6)' : 'transparent' }}; background: #f3f4f6;"
                            onmouseenter="this.querySelector('.media-delete').style.opacity=1"
                            onmouseleave="this.querySelector('.media-delete').style.opacity=0">
                            <div style="position: absolute; top: 6px; left: 6px; z-index: 2;" @click.stop>
                                <input type="checkbox" wire:model.live="selected" value="{{ $item->id }}"
                                    style="width: 18px; height: 18px; cursor: pointer; accent-color: var(--primary-500, #3b82f6);" />
                            </div>
                            <button type="button" class="media-delete" @click.stop
                                @click="if(confirm('{{ __('kit::admin.confirm_delete') }}')) { $wire.deleteSingle({{ $item->id }}) }"
                                style="position: absolute; top: 6px; right: 6px; z-index: 2; padding: 5px; background: #ef4444; border-radius: 6px; border: none; cursor: pointer; opacity: 0; transition: opacity 0.2s;">
                                <svg style="width: 14px; height: 14px; color: white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                            </button>
                            <div style="aspect-ratio: 1; cursor: pointer;"
                                @click="openEdit(@js(['id' => $item->id, 'name' => $item->name, 'url' => $item->getUrl(), 'alt' => $item->alt ?? [], 'width' => $item->width, 'height' => $item->height, 'size' => $item->size, 'mime_type' => $item->mime_type, 'has_responsive' => !empty($item->responsive_images)]))">
                                @if(str_starts_with($item->mime_type, 'image/'))
                                    <img src="{{ $item->getUrl() }}" alt="{{ $item->name }}" style="width: 100%; height: 100%; object-fit: cover;" loading="lazy" />
                                @elseif(str_starts_with($item->mime_type, 'video/'))
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #1f2937;">
                                        <svg style="width: 40px; height: 40px; color: white;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" />
                                        </svg>
                                    </div>
                                @else
                                    <div style="width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; background: #f9fafb;">
                                        <svg style="width: 40px; height: 40px; color: #9ca3af;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                    </div>
                                @endif
                            </div>
                            <div style="padding: 6px 8px;">
                                <p style="font-size: 11px; color: #6b7280; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; margin: 0;">{{ $item->name }}</p>
                                <p style="font-size: 10px; color: #9ca3af; margin: 2px 0 0 0;">
                                    @if($item->width){{ $item->width }}×{{ $item->height }} · @endif{{ format_bytes($item->size) }}
                                </p>
                            </div>
                        </div>
                    @endforeach

                    {{-- Edit Modal --}}
                    <div x-show="editItem" @keydown.escape.window="editItem = null"
                        style="display: none; position: fixed; inset: 0; z-index: 999; padding: 16px;">
                        <div @click="editItem = null" style="position: absolute; inset: 0; background: rgba(0,0,0,0.6);"></div>
                        <div style="position: relative; margin: auto; margin-top: 5vh; background: white; border-radius: 12px; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); width: 100%; max-width: 800px; max-height: 85vh; display: flex; overflow: hidden;">
                            <div style="flex: 1; background: #111; display: flex; align-items: center; justify-content: center; min-height: 400px;">
                                <template x-if="editItem?.mime_type?.startsWith('image/')">
                                    <img :src="editItem?.url" :alt="editItem?.name" style="max-width: 100%; max-height: 80vh; object-fit: contain;" />
                                </template>
                                <template x-if="editItem?.mime_type?.startsWith('video/')">
                                    <video :src="editItem?.url" controls style="max-width: 100%; max-height: 80vh;"></video>
                                </template>
                                <template x-if="!editItem?.mime_type?.startsWith('image/') && !editItem?.mime_type?.startsWith('video/')">
                                    <div style="color: white; text-align: center;">
                                        <svg style="width: 64px; height: 64px; margin: 0 auto 12px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        <p x-text="editItem?.name" style="margin: 0;"></p>
                                    </div>
                                </template>
                            </div>
                            <div style="width: 300px; padding: 24px; display: flex; flex-direction: column; gap: 16px; overflow-y: auto; border-left: 1px solid #e5e7eb;">
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <h3 style="font-size: 16px; font-weight: 600; margin: 0;">{{ __('kit::admin.edit') }}</h3>
                                    <button type="button" @click="editItem = null" style="background: none; border: none; cursor: pointer; color: #9ca3af;">
                                        <svg style="width: 18px; height: 18px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <div style="font-size: 12px; color: #9ca3af;">
                                    <span x-text="(editItem?.width ? editItem.width + '×' + editItem.height + ' · ' : '') + editItem?.mime_type"></span>
                                </div>
                                <div>
                                    <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">{{ __('kit::admin.name') }}</label>
                                    <input type="text" x-model="editName" style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #111827; outline: none; box-sizing: border-box;" />
                                </div>
                                <template x-for="lang in languages" :key="lang.slug">
                                    <div>
                                        <label style="display: block; font-size: 14px; font-weight: 500; color: #374151; margin-bottom: 4px;">
                                            {{ __('kit::admin.alt_text') }}
                                            <template x-if="languages.length > 1"><span style="font-weight: 400; color: #6b7280;" x-text="'(' + lang.name + ')'"></span></template>
                                        </label>
                                        <input type="text" x-model="editAlt[lang.slug]" style="width: 100%; padding: 8px 12px; font-size: 14px; border: 1px solid #d1d5db; border-radius: 8px; background: white; color: #111827; outline: none; box-sizing: border-box;" />
                                    </div>
                                </template>
                                {{-- Process button for images --}}
                                <template x-if="editItem?.mime_type?.startsWith('image/') && editItem?.mime_type !== 'image/svg+xml'">
                                    <button type="button"
                                        @click="$wire.processMedia(editItem.id); editItem = null;"
                                        style="width: 100%; padding: 8px; font-size: 13px; font-weight: 500; border-radius: 8px; cursor: pointer; margin-top: auto;"
                                        :style="editItem?.has_responsive ? 'color: #374151; background: #f3f4f6; border: 1px solid #d1d5db;' : 'color: #92400e; background: #fef3c7; border: 1px solid #fbbf24;'"
                                    >
                                        <span x-show="!editItem?.has_responsive">{{ __('kit::admin.generate_responsive') }}</span>
                                        <span x-show="editItem?.has_responsive" style="display:none;">{{ __('kit::admin.regenerate_responsive_images') }}</span>
                                    </button>
                                </template>

                                <div style="display: flex; gap: 8px; padding-top: 16px; border-top: 1px solid #e5e7eb;">
                                    <button type="button" @click="saveEdit()" :style="saving ? 'opacity: 0.5; pointer-events: none;' : ''"
                                        style="flex: 1; padding: 8px; font-size: 14px; font-weight: 500; color: white; background: var(--primary-600, #2563eb); border: none; border-radius: 8px; cursor: pointer;">
                                        <span x-show="!saving">{{ __('kit::admin.save') }}</span><span x-show="saving" style="display: none;">...</span>
                                    </button>
                                    <button type="button" @click="if(confirm('{{ __('kit::admin.confirm_delete') }}')) { $wire.deleteSingle(editItem.id); editItem = null; }"
                                        style="padding: 8px 12px; font-size: 14px; font-weight: 500; color: white; background: #ef4444; border: none; border-radius: 8px; cursor: pointer;">
                                        {{ __('kit::admin.delete') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if($media->hasPages())
                    <div style="margin-top: 24px; display: flex; align-items: center; justify-content: center; gap: 4px;">
                        @if($media->onFirstPage())
                            <span style="padding: 8px 12px; font-size: 14px; color: #9ca3af; border: 1px solid #e5e7eb; border-radius: 6px;">←</span>
                        @else
                            <button type="button" wire:click="previousPage" style="padding: 8px 12px; font-size: 14px; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">←</button>
                        @endif
                        @foreach($media->getUrlRange(max(1, $media->currentPage() - 2), min($media->lastPage(), $media->currentPage() + 2)) as $page => $url)
                            @if($page == $media->currentPage())
                                <span style="padding: 8px 12px; font-size: 14px; color: white; background: var(--primary-600, #2563eb); border-radius: 6px; font-weight: 500;">{{ $page }}</span>
                            @else
                                <button type="button" wire:click="gotoPage({{ $page }})" style="padding: 8px 12px; font-size: 14px; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">{{ $page }}</button>
                            @endif
                        @endforeach
                        @if($media->hasMorePages())
                            <button type="button" wire:click="nextPage" style="padding: 8px 12px; font-size: 14px; color: #374151; background: white; border: 1px solid #d1d5db; border-radius: 6px; cursor: pointer;">→</button>
                        @else
                            <span style="padding: 8px 12px; font-size: 14px; color: #9ca3af; border: 1px solid #e5e7eb; border-radius: 6px;">→</span>
                        @endif
                    </div>
                @endif
            @elseif(count($folders) === 0)
                <div style="text-align: center; padding: 64px 0; color: #6b7280;">
                    <svg style="width: 48px; height: 48px; margin: 0 auto 12px; color: #d1d5db;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" />
                    </svg>
                    <p>{{ __('kit::admin.no_images_found') }}</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
