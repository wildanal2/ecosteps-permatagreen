<flux:modal name="upload-harian" class="max-w-md lg:w-lg">
    <div class="space-y-6">
        <div>
            <flux:heading size="lg">Kirim Laporan Aktivitas Harian</flux:heading>
            <flux:text class="mt-2">Unggah bukti aktivitas jalanmu hari ini untuk verifikasi</flux:text>
        </div>


        <div x-data="{
            isDragging: false,
            handleDrop(e) {
                this.isDragging = false;
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    @this.upload('photo', files[0]);
                }
            }
        }" @dragover.prevent="isDragging = true" @dragleave.prevent="isDragging = false"
            @drop.prevent="handleDrop($event)" :class="isDragging ? 'border-blue-500 bg-blue-100' : 'bg-blue-50'"
            class="rounded-lg p-6 flex-col items-center min-h-64 border-2 border-dashed border-blue-200 transition-colors">
            @if ($isUploading)
                <div class="w-full flex flex-col items-center justify-center">
                    <div class="w-16 h-16 border-4 border-blue-500 border-t-transparent rounded-full animate-spin mb-4">
                    </div>
                    <p class="text-gray-700 font-medium">Mengupload gambar...</p>
                    <div class="w-full max-w-xs bg-gray-200 rounded-full h-2.5 mt-4">
                        <div class="bg-blue-600 h-2.5 rounded-full transition-all duration-300 ease-in-out"
                            :style="`width: ${@js($uploadProgress)}%`"></div>
                    </div>
                    <p class="text-sm text-gray-600 mt-2">{{ $uploadProgress }}% selesai</p>
                </div>
            @elseif($showSuccess)
                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            xmlns="http://www.w3.org/2000/svg">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <h3 class="text-lg font-medium text-gray-900 mb-1">Upload Berhasil!</h3>
                    <p class="text-gray-600">Gambar telah berhasil diupload ke server.</p>
                </div>
            @else
                @if ($photo)
                    <div class="text-center">
                        <img src="{{ $photo->temporaryUrl() }}" class="max-h-48 rounded-lg mb-3 mx-auto" />
                        <p class="text-sm text-gray-700 font-medium">{{ $photo->getClientOriginalName() }}</p>
                        <p class="text-xs text-gray-500">{{ number_format($photo->getSize() / 1024, 2) }} KB</p>
                        <button type="button" wire:click="photo = null"
                            class="mt-2 text-red-600 text-sm hover:underline">
                            Hapus
                        </button>
                    </div>
                @else
                    <label class="w-full flex flex-col items-center cursor-pointer">
                        <i class="ph ph-cloud-arrow-up px-2 py-1 text-2xl bg-white rounded mb-3"></i>
                        <span class="font-medium text-gray-700 mb-2">Pilih file atau drag & drop</span>
                        <input type="file" wire:model="photo" accept="image/jpeg,image/png,image/jpg"
                            class="hidden" />
                        <span class="text-xs text-gray-500 mb-4">JPEG atau PNG format, maksimal 5 MB</span>
                        <span class="inline-block">
                            <flux:button type="button" variant="outline" color="blue">
                                Cari File
                            </flux:button>
                        </span>
                    </label>
                @endif

                <div wire:loading wire:target="photo" class="mt-3">
                    <p class="text-sm text-blue-600">Mengupload...</p>
                </div>

                @error('photo')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            @endif
        </div>

        <div class="mt-4 flex justify-between">
            <flux:modal.close>
                <flux:button type="button" variant="ghost">Batal</flux:button>
            </flux:modal.close>

            @if (!$isUploading && !$showSuccess && $photo)
                <flux:spacer />
                <flux:button type="button" variant="primary" color="blue" wire:click="uploadReport">
                    <span wire:loading.remove wire:target="uploadReport">Kirim Laporan</span>
                    <span wire:loading wire:target="uploadReport">Mengirim...</span>
                </flux:button>
            @elseif($showSuccess)
                <flux:spacer />
                <flux:button type="button" variant="primary" color="blue" wire:click="$refresh">
                    Upload Lagi
                </flux:button>
            @endif
        </div>
    </div>
</flux:modal>
