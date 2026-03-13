@props([
    'name' => 'avatar',
    'currentImage' => null,
    'required' => false
])

<div x-data="avatarUpload()" class="space-y-4">
    <!-- Preview atual ou placeholder -->
    <div class="flex items-center space-x-6">
        <div class="shrink-0">
            <div class="relative" style="width: 150px; height: 150px;">
                <img
                    x-show="previewUrl || currentImage"
                    :src="previewUrl || currentImage"
                    alt="Preview"
                    class="rounded-full object-cover border-4 border-gray-200 dark:border-gray-600 shadow-lg"
                    style="width: 150px; height: 150px; max-width: 150px; max-height: 150px;"
                    x-cloak
                >
                <div
                    x-show="!previewUrl && !currentImage"
                    class="rounded-full bg-linear-to-br from-blue-500 to-purple-600 flex items-center justify-center text-white shadow-lg"
                    style="width: 150px; height: 150px;"
                >
                    <svg class="w-16 h-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>

                <!-- Badge de upload -->
                <button
                    @click="$refs.fileInput.click()"
                    type="button"
                    class="absolute bottom-0 right-0 bg-blue-600 hover:bg-blue-700 text-white rounded-full p-2 shadow-lg transition-colors"
                    title="Escolher foto"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </button>
            </div>
        </div>

        <div class="flex-1">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2">
                Foto do Perfil
                @if($required)
                    <span class="text-red-500">*</span>
                @endif
            </h3>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-3">
                Escolha uma foto de perfil. A imagem será redimensionada para 500x500px.
            </p>

            <!-- Input oculto -->
            <input
                type="file"
                x-ref="fileInput"
                @change="handleFileSelect($event)"
                accept="image/jpeg,image/jpg,image/png,image/gif"
                class="hidden"
            >

            <!-- Botões de ação -->
            <div class="flex items-center space-x-3">
                <button
                    @click="$refs.fileInput.click()"
                    type="button"
                    class="px-4 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors text-sm font-medium"
                >
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                    </svg>
                    Escolher Arquivo
                </button>

                <button
                    x-show="previewUrl"
                    @click="removeImage()"
                    type="button"
                    class="px-4 py-2 bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors text-sm font-medium"
                    x-cloak
                >
                    <svg class="w-4 h-4 inline-block mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    Remover
                </button>
            </div>

            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                JPG, PNG ou GIF (máx. 2MB)
            </p>
        </div>
    </div>

    <!-- Modal de Crop -->
    <div
        x-show="showCropModal"
        x-cloak
        class="avatar-upload-modal fixed inset-0 overflow-y-auto"
        @keydown.escape.window="closeCropModal()"
        style="z-index: 99999 !important;"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div
                x-show="showCropModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-gray-900 bg-opacity-75"
                style="z-index: 9999;"
                @click="closeCropModal()"
            ></div>

            <!-- Spacer para centralização -->
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div
                x-show="showCropModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
                style="z-index: 10000;"
                @click.stop
            >
                <!-- Header -->
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Ajustar Foto do Perfil
                        </h3>
                        <button
                            @click="closeCropModal()"
                            type="button"
                            class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-lg p-1"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Container do Cropper -->
                    <div class="w-full bg-gray-100 dark:bg-gray-900 rounded-lg overflow-hidden" style="max-height: 400px;">
                        <img x-ref="cropperImage" :src="selectedImage" class="max-w-full block mx-auto">
                    </div>

                    <p class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                        💡 Arraste para posicionar e use a roda do mouse para ajustar o zoom.
                    </p>
                </div>

                <!-- Footer com Botões -->
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                    <button
                        @click="closeCropModal()"
                        type="button"
                        class="w-full sm:w-auto inline-flex justify-center items-center rounded-lg border border-gray-300 dark:border-gray-600 shadow-sm px-6 py-2.5 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        Cancelar
                    </button>
                    <button
                        @click="cropImage()"
                        type="button"
                        class="w-full sm:w-auto inline-flex justify-center items-center rounded-lg border border-transparent shadow-sm px-6 py-2.5 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors"
                    >
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Aplicar Corte
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden input com a imagem processada -->
    <input type="hidden" :name="name" x-model="croppedImageData">
</div>

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<style>
    [x-cloak] { display: none !important; }

    /* Garantir que o modal fique sempre no topo */
    .avatar-upload-modal {
        z-index: 99999 !important;
        position: fixed !important;
    }

    .avatar-upload-modal .cropper-container {
        max-height: 400px !important;
    }

    /* Garantir visibilidade dos botões */
    .avatar-upload-modal button {
        position: relative !important;
        z-index: 100000 !important;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
function avatarUpload() {
    return {
        name: '{{ $name }}',
        currentImage: '{{ $currentImage }}',
        previewUrl: null,
        selectedImage: null,
        croppedImageData: '',
        showCropModal: false,
        cropper: null,

        handleFileSelect(event) {
            const file = event.target.files[0];

            if (!file) return;

            // Validar tamanho (2MB)
            if (file.size > 2 * 1024 * 1024) {
                alert('A imagem deve ter no máximo 2MB');
                event.target.value = '';
                return;
            }

            // Validar tipo
            if (!file.type.match('image/(jpeg|jpg|png|gif)')) {
                alert('Apenas arquivos JPG, PNG ou GIF são permitidos');
                event.target.value = '';
                return;
            }

            // Ler arquivo
            const reader = new FileReader();
            reader.onload = (e) => {
                this.selectedImage = e.target.result;
                this.showCropModal = true;

                // Inicializar cropper após o modal aparecer
                this.$nextTick(() => {
                    this.initCropper();
                });
            };
            reader.readAsDataURL(file);
        },

        initCropper() {
            if (this.cropper) {
                this.cropper.destroy();
            }

            this.cropper = new Cropper(this.$refs.cropperImage, {
                aspectRatio: 1,
                viewMode: 2,
                dragMode: 'move',
                autoCropArea: 1,
                restore: false,
                guides: true,
                center: true,
                highlight: false,
                cropBoxMovable: true,
                cropBoxResizable: true,
                toggleDragModeOnDblclick: false,
                minCropBoxWidth: 200,
                minCropBoxHeight: 200,
            });
        },

        cropImage() {
            if (!this.cropper) return;

            // Obter canvas com a imagem cortada e redimensionada
            const canvas = this.cropper.getCroppedCanvas({
                width: 500,
                height: 500,
                imageSmoothingEnabled: true,
                imageSmoothingQuality: 'high',
            });

            // Converter para blob e depois para base64
            canvas.toBlob((blob) => {
                const reader = new FileReader();
                reader.onloadend = () => {
                    this.croppedImageData = reader.result;
                    this.previewUrl = reader.result;
                    this.closeCropModal();
                };
                reader.readAsDataURL(blob);
            }, 'image/jpeg', 0.9);
        },

        closeCropModal() {
            this.showCropModal = false;
            if (this.cropper) {
                this.cropper.destroy();
                this.cropper = null;
            }
            // Limpar o input file
            this.$refs.fileInput.value = '';
        },

        removeImage() {
            this.previewUrl = null;
            this.croppedImageData = '';
            this.selectedImage = null;
            this.$refs.fileInput.value = '';
        }
    }
}
</script>
@endpush
