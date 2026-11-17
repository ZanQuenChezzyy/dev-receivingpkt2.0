@php
    $fieldWrapperView = $getFieldWrapperView();
    $statePath = $getStatePath();
    $id = $getId();
    $uploadUrl = route('filament.admin.resources.invoicing-receipts.import_chunks', absolute: false);
    $deleteUrl = route('filament.admin.resources.invoicing-receipts.import_chunks.destroy', absolute: false);
    $maxBytes = \App\Http\Controllers\Admin\ChunkedImportUploadController::MAX_FILE_BYTES;
@endphp

<x-dynamic-component
    :component="$fieldWrapperView"
    :field="$field"
    label-tag="div"
>
    <div
        id="{{ $id }}"
        x-ref="uploaderRoot"
        data-upload-url="{{ $uploadUrl }}"
        data-delete-url="{{ $deleteUrl }}"
        data-csrf-token="{{ csrf_token() }}"
        data-max-bytes="{{ $maxBytes }}"
        data-chunk-size="786432"
        x-data="{
            state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$statePath}')") }},
            uploadUrl: '',
            deleteUrl: '',
            fallbackCsrfToken: '',
            maxBytes: 52428800,
            chunkSize: 786432,
            isDragging: false,
            uploading: false,
            progress: 0,
            uploadedChunks: 0,
            totalChunks: 0,
            fileName: '',
            fileSize: 0,
            errorMessage: '',
            currentUploadId: null,
            abortController: null,
            init() {
                const root = this.$root || this.$refs.uploaderRoot;
                if (root) {
                    this.uploadUrl = root.dataset.uploadUrl || '';
                    this.deleteUrl = root.dataset.deleteUrl || '';
                    this.fallbackCsrfToken = root.dataset.csrfToken || '';
                    this.maxBytes = Number(root.dataset.maxBytes || 52428800);
                    this.chunkSize = Number(root.dataset.chunkSize || 786432);
                }

                this.$watch('state', (value) => {
                    if (!value && !this.uploading) {
                        this.resetLocalUi();
                    }
                });
            },
            resetLocalUi() {
                this.fileName = '';
                this.fileSize = 0;
                this.progress = 0;
                this.uploadedChunks = 0;
                this.totalChunks = 0;
                if (this.$refs.fileInput) {
                    this.$refs.fileInput.value = '';
                }
            },
            getCsrfToken() {
                return document.querySelector('meta[name=csrf-token]')?.getAttribute('content')
                    || this.fallbackCsrfToken
                    || this.$root?.dataset?.csrfToken
                    || '';
            },
            generateUuid() {
                if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
                    return crypto.randomUUID();
                }
                if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
                    const bytes = crypto.getRandomValues(new Uint8Array(16));
                    bytes[6] = (bytes[6] & 0x0f) | 0x40;
                    bytes[8] = (bytes[8] & 0x3f) | 0x80;
                    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, '0')).join('');
                    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
                }
                return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
                    const r = (Math.random() * 16) | 0;
                    const v = c === 'x' ? r : (r & 0x3) | 0x8;
                    return v.toString(16);
                });
            },
            formatBytes(bytes) {
                if (!bytes) {
                    return '0 B';
                }
                const mb = bytes / (1024 * 1024);
                if (mb >= 1) {
                    return mb.toFixed(2) + ' MB';
                }
                return Math.max(1, Math.round(bytes / 1024)) + ' KB';
            },
            handleDrop(event) {
                this.isDragging = false;
                const file = event.dataTransfer?.files?.[0];
                if (file) {
                    this.startUpload(file);
                }
            },
            handleFileSelect(event) {
                const file = event.target?.files?.[0];
                if (file) {
                    this.startUpload(file);
                }
            },
            async deleteRemoteTemp({ path = null, uploadId = null }) {
                const targetUrl = this.deleteUrl || this.$root?.dataset?.deleteUrl || '';
                if (!targetUrl || (!path && !uploadId)) {
                    return;
                }

                try {
                    await fetch(targetUrl, {
                        method: 'DELETE',
                        credentials: 'same-origin',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.getCsrfToken(),
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            path: path || null,
                            upload_id: uploadId || null,
                        }),
                    });
                } catch {
                    // Abaikan kegagalan cleanup
                }
            },
            async clearFile(notifyServer = true) {
                if (this.abortController) {
                    this.abortController.abort();
                    this.abortController = null;
                }

                const oldPath = this.state;
                const oldUploadId = this.currentUploadId;

                this.uploading = false;
                this.state = null;
                this.currentUploadId = null;
                this.errorMessage = '';
                this.resetLocalUi();

                if (notifyServer) {
                    await this.deleteRemoteTemp({ path: oldPath, uploadId: oldUploadId });
                }
            },
            async startUpload(file) {
                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!['xlsx', 'csv'].includes(ext)) {
                    this.errorMessage = 'Format file tidak didukung. Pilih file Excel (.xlsx) atau CSV (.csv).';
                    return;
                }

                const maxBytes = this.maxBytes || Number(this.$root?.dataset?.maxBytes || 52428800);
                if (file.size <= 0) {
                    this.errorMessage = 'File kosong (0 byte). Silakan pilih file yang berisi data.';
                    return;
                }

                if (file.size > maxBytes) {
                    this.errorMessage = 'Ukuran file melebihi batas maksimal 50 MB.';
                    return;
                }

                const uploadUrl = this.uploadUrl || this.$root?.dataset?.uploadUrl || '';
                if (!uploadUrl) {
                    this.errorMessage = 'Endpoint unggah tidak ditemukan. Silakan muat ulang halaman.';
                    return;
                }

                await this.clearFile(true);

                const chunkSize = this.chunkSize || Number(this.$root?.dataset?.chunkSize || 786432);
                const totalChunks = Math.ceil(file.size / chunkSize);
                const uploadId = this.generateUuid();
                const controller = new AbortController();

                this.fileName = file.name;
                this.fileSize = file.size;
                this.totalChunks = totalChunks;
                this.uploadedChunks = 0;
                this.progress = 0;
                this.errorMessage = '';
                this.uploading = true;
                this.currentUploadId = uploadId;
                this.abortController = controller;

                try {
                    for (let chunkIndex = 0; chunkIndex < totalChunks; chunkIndex++) {
                        if (controller.signal.aborted) {
                            return;
                        }

                        const start = chunkIndex * chunkSize;
                        const end = Math.min(start + chunkSize, file.size);
                        const blob = file.slice(start, end);

                        const formData = new FormData();
                        formData.append('upload_id', uploadId);
                        formData.append('chunk_index', String(chunkIndex));
                        formData.append('total_chunks', String(totalChunks));
                        formData.append('total_size', String(file.size));
                        formData.append('original_name', file.name);
                        formData.append('chunk', blob, `${file.name}.part`);

                        const response = await fetch(uploadUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            signal: controller.signal,
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });

                        let payload = {};
                        try {
                            payload = await response.json();
                        } catch {
                            payload = {};
                        }

                        if (!response.ok) {
                            const message = response.status === 419
                                ? 'Sesi halaman kedaluwarsa. Muat ulang halaman lalu coba unggah kembali.'
                                : (payload.message || `Gagal mengunggah bagian file (${response.status}).`);
                            throw new Error(message);
                        }

                        this.uploadedChunks = chunkIndex + 1;
                        this.progress = Math.round((this.uploadedChunks / totalChunks) * 100);

                        if (payload.status === 'completed' && payload.path) {
                            this.state = payload.path;
                            this.uploading = false;
                            this.currentUploadId = null;
                            this.abortController = null;
                        }
                    }
                } catch (err) {
                    if (err && err.name === 'AbortError') {
                        return;
                    }

                    this.uploading = false;
                    this.state = null;
                    const failedUploadId = this.currentUploadId;
                    this.currentUploadId = null;
                    this.abortController = null;
                    this.errorMessage = (err && err.message) ? err.message : 'Terjadi kesalahan saat mengunggah file.';

                    await this.deleteRemoteTemp({ uploadId: failedUploadId });
                }
            },
        }"
        class="space-y-2.5"
    >
        <input
            type="file"
            x-ref="fileInput"
            accept=".xlsx,.csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,text/csv"
            class="hidden"
            x-on:change="handleFileSelect($event)"
        />

        <!-- Dropzone saat belum ada file / sedang gagal -->
        <div
            x-show="!fileName && !state"
            x-on:click="$refs.fileInput.click()"
            x-on:dragover.prevent="isDragging = true"
            x-on:dragleave.prevent="isDragging = false"
            x-on:drop.prevent="handleDrop($event)"
            x-bind:class="isDragging
                ? 'border-primary-500 bg-primary-50/70 dark:bg-primary-950/30'
                : 'border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900/70 hover:border-primary-400 dark:hover:border-primary-500'"
            class="cursor-pointer rounded-xl border-2 border-dashed px-4 py-6 text-center transition-colors"
        >
            <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                <x-filament::icon icon="heroicon-o-arrow-up-tray" class="h-5 w-5" />
            </div>
            <p class="text-sm font-medium text-gray-700 dark:text-gray-200">
                Seret &amp; lepas file di sini atau <span class="text-primary-600 dark:text-primary-400 underline">Pilih File</span>
            </p>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                Format: .xlsx atau .csv (diunggah bertahap per 768 KB)
            </p>
        </div>

        <!-- Status progress upload & hasil siap impor -->
        <div
            x-show="fileName || state"
            style="display: none;"
            class="rounded-xl border px-4 py-3 transition-colors"
            x-bind:class="errorMessage
                ? 'border-danger-300 bg-danger-50/70 dark:border-danger-800 dark:bg-danger-950/30'
                : (state && !uploading
                    ? 'border-success-300 bg-success-50/70 dark:border-success-800 dark:bg-success-950/30'
                    : 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900/70')"
        >
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-900 dark:text-white" x-text="fileName || state"></p>
                    <p class="mt-0.5 text-xs text-gray-600 dark:text-gray-400">
                        <span x-show="fileSize > 0" x-text="formatBytes(fileSize)"></span>
                        <span x-show="uploading">
                            &bull; Mengunggah <span x-text="uploadedChunks"></span>/<span x-text="totalChunks"></span> bagian (<span x-text="progress"></span>%)
                        </span>
                        <span x-show="state && !uploading" class="font-medium text-success-700 dark:text-success-400">
                            &bull; Siap diimpor
                        </span>
                    </p>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <button
                        type="button"
                        x-show="!uploading"
                        x-on:click="$refs.fileInput.click()"
                        class="rounded-lg border border-gray-300 bg-white px-2.5 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700"
                    >
                        Ganti
                    </button>
                    <button
                        type="button"
                        x-on:click="clearFile(true)"
                        class="rounded-lg border border-danger-300 bg-white px-2.5 py-1 text-xs font-medium text-danger-600 hover:bg-danger-50 dark:border-danger-800 dark:bg-gray-800 dark:text-danger-400 dark:hover:bg-danger-950/50"
                        x-text="uploading ? 'Batal' : 'Hapus'"
                    ></button>
                </div>
            </div>

            <div x-show="uploading" class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                <div
                    class="h-full rounded-full bg-primary-600 transition-all duration-150"
                    x-bind:style="'width: ' + progress + '%'"
                ></div>
            </div>
        </div>

        <p x-show="errorMessage" style="display: none;" x-text="errorMessage" class="text-xs font-medium text-danger-600 dark:text-danger-400"></p>
    </div>
</x-dynamic-component>
