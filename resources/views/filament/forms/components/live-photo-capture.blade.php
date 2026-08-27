@php
    $statePath = $getStatePath();
@endphp

<div
    x-data="{
        state: $wire.$entangle('{{ $statePath }}'),
        isCameraOpen: false,
        stream: null,
        capturedImage: null,
        cameraError: null,
        isProcessing: false,

        async openCamera() {
            this.cameraError = null;
            this.capturedImage = null;
            this.isCameraOpen = true;

            await this.$nextTick();

            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Camera access is not supported on this browser or connection is not secure (HTTPS required).');
                }

                this.stream = await navigator.mediaDevices.getUserMedia({
                    video: {
                        facingMode: 'user',
                        width: { ideal: 1280 },
                        height: { ideal: 720 }
                    },
                    audio: false
                });

                const video = this.$refs.video;
                if (video) {
                    video.srcObject = this.stream;
                    await video.play();
                }
            } catch (err) {
                this.cameraError = err.message || 'Unable to access camera. Please allow camera permissions.';
                this.stopCamera();
            }
        },

        takeSnapshot() {
            const video = this.$refs.video;
            const canvas = this.$refs.canvas;

            if (!video || !canvas) return;

            const width = video.videoWidth || 640;
            const height = video.videoHeight || 480;

            canvas.width = width;
            canvas.height = height;

            const ctx = canvas.getContext('2d');
            ctx.drawImage(video, 0, 0, width, height);

            // Future face detection / liveness hooks can be called here
            this.capturedImage = canvas.toDataURL('image/jpeg', 0.9);
            this.stopCamera();
        },

        retake() {
            this.capturedImage = null;
            this.openCamera();
        },

        confirm() {
            if (this.capturedImage) {
                this.state = this.capturedImage;
                this.isCameraOpen = false;
                this.stopCamera();
            }
        },

        clearPhoto() {
            this.state = null;
            this.capturedImage = null;
            this.stopCamera();
        },

        stopCamera() {
            if (this.stream) {
                this.stream.getTracks().forEach(track => track.stop());
                this.stream = null;
            }
        },

        closeModal() {
            this.isCameraOpen = false;
            this.stopCamera();
        }
    }"
    class="fi-fo-live-photo-capture space-y-3"
>
    <!-- Hidden Canvas for frame extraction -->
    <canvas x-ref="canvas" class="hidden"></canvas>

    <!-- Confirmed Photo Preview (When Photo is already confirmed) -->
    <template x-if="state">
        <div class="relative inline-block border-2 border-teal-500 rounded-xl overflow-hidden shadow-sm bg-gray-50 dark:bg-gray-900 p-2">
            <div class="relative w-48 h-48 rounded-lg overflow-hidden bg-black/5 flex items-center justify-center">
                <img :src="state" alt="Live Photo" class="w-full h-full object-cover" />
                <div class="absolute top-2 right-2 bg-teal-600 text-white rounded-full p-1 shadow-md">
                    <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                </div>
            </div>
            <div class="mt-2 flex items-center justify-between gap-2">
                <span class="text-xs font-semibold text-teal-700 dark:text-teal-400 flex items-center gap-1">
                    <x-filament::icon icon="heroicon-m-camera" class="w-3.5 h-3.5" />
                    Live Photo Captured
                </span>
                <button
                    type="button"
                    x-on:click="openCamera()"
                    class="text-xs text-gray-500 hover:text-teal-600 underline font-medium cursor-pointer"
                >
                    Retake
                </button>
            </div>
        </div>
    </template>

    <!-- Initial Button Trigger (When no photo confirmed yet) -->
    <template x-if="!state && !isCameraOpen">
        <div class="border-2 border-dashed border-gray-300 dark:border-gray-700 rounded-2xl p-6 text-center bg-gray-50/50 dark:bg-gray-900/30 hover:border-teal-500 transition">
            <div class="w-14 h-14 mx-auto rounded-full bg-teal-50 dark:bg-teal-950/50 border border-teal-200 dark:border-teal-800 flex items-center justify-center text-teal-600 dark:text-teal-400 mb-3 shadow-xs">
                <x-filament::icon icon="heroicon-o-camera" class="w-7 h-7" />
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Live Selfie Photo Required</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                Photo must be taken directly via device camera. Gallery and file uploads are strictly restricted for verification security.
            </p>
            <div class="mt-4">
                <button
                    type="button"
                    x-on:click="openCamera()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold text-white bg-teal-600 hover:bg-teal-700 focus:ring-4 focus:ring-teal-500/20 shadow-sm transition cursor-pointer"
                >
                    <x-filament::icon icon="heroicon-m-camera" class="w-4 h-4" />
                    <span>Open Camera & Take Live Photo</span>
                </button>
            </div>
        </div>
    </template>

    <!-- Live Camera Capture Modal / Viewfinder Container -->
    <div
        x-cloak
        x-show="isCameraOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
    >
        <div
            x-on:click.outside="closeModal()"
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-200 dark:border-gray-800"
        >
            <!-- Modal Header -->
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-camera" class="w-5 h-5 text-teal-600" />
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Live Photo Capture</h3>
                </div>
                <button
                    type="button"
                    x-on:click="closeModal()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                </button>
            </div>

            <!-- Modal Body: Video Stream or Captured Preview -->
            <div class="p-5 space-y-4">
                <template x-if="cameraError">
                    <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-xs text-red-700 dark:text-red-300">
                        <p class="font-bold flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-4 h-4" />
                            Camera Access Error
                        </p>
                        <p class="mt-1" x-text="cameraError"></p>
                    </div>
                </template>

                <!-- Active Video Stream -->
                <div x-show="!capturedImage && !cameraError" class="relative aspect-4/3 w-full rounded-xl overflow-hidden bg-black shadow-inner flex items-center justify-center">
                    <video
                        x-ref="video"
                        autoplay
                        playsinline
                        muted
                        class="w-full h-full object-cover transform -scale-x-100"
                    ></video>
                    <!-- Face Guide Oval Overlay -->
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                        <div class="w-48 h-60 border-2 border-dashed border-white/70 rounded-full shadow-lg"></div>
                    </div>
                    <div class="absolute bottom-3 left-0 right-0 text-center pointer-events-none">
                        <span class="inline-block px-3 py-1 bg-black/60 backdrop-blur-xs text-white text-[11px] font-medium rounded-full">
                            Position your face within the frame
                        </span>
                    </div>
                </div>

                <!-- Captured Image Preview -->
                <div x-show="capturedImage" class="relative aspect-4/3 w-full rounded-xl overflow-hidden bg-black shadow-inner flex items-center justify-center">
                    <img :src="capturedImage" alt="Captured Selfie" class="w-full h-full object-cover transform -scale-x-100" />
                </div>
            </div>

            <!-- Modal Footer Controls -->
            <div class="px-5 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <template x-if="!capturedImage && !cameraError">
                    <div class="w-full flex items-center justify-between">
                        <button
                            type="button"
                            x-on:click="closeModal()"
                            class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg transition"
                        >
                            Cancel
                        </button>
                        <button
                            type="button"
                            x-on:click="takeSnapshot()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-md transition cursor-pointer"
                        >
                            <span class="w-3 h-3 rounded-full bg-white animate-ping mr-1"></span>
                            Capture Photo
                        </button>
                    </div>
                </template>

                <template x-if="capturedImage">
                    <div class="w-full flex items-center justify-between gap-3">
                        <button
                            type="button"
                            x-on:click="retake()"
                            class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 hover:bg-gray-100 rounded-lg transition cursor-pointer"
                        >
                            <x-filament::icon icon="heroicon-m-arrow-path" class="w-4 h-4" />
                            Retake
                        </button>
                        <button
                            type="button"
                            x-on:click="confirm()"
                            class="inline-flex items-center gap-1.5 px-5 py-2 text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 rounded-lg shadow-md transition cursor-pointer"
                        >
                            <x-filament::icon icon="heroicon-m-check" class="w-4 h-4" />
                            Confirm Photo
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
