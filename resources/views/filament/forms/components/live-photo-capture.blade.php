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

        // === QR Code / Phone Camera Mode ===
        isQrMode: false,
        qrSessionToken: null,
        qrCaptureUrl: null,
        qrDataUri: null,
        qrIsLoading: false,
        qrError: null,
        qrPollTimer: null,
        qrExpiresSeconds: 0,
        qrExpiresTimer: null,

        async openCamera() {
            this.cameraError = null;
            this.capturedImage = null;
            this.isQrMode = false;
            this.stopQrPolling();
            this.isCameraOpen = true;

            await this.$nextTick();

            try {
                if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                    throw new Error('Camera access is not supported on this browser or connection is not secure (HTTPS required).');
                }

                let stream = null;
                const constraintFallbacks = [
                    { video: { facingMode: { ideal: 'user' }, width: { ideal: 1280 }, height: { ideal: 720 } }, audio: false },
                    { video: { facingMode: 'user' }, audio: false },
                    { video: true, audio: false },
                ];
                let lastErr = null;
                for (const constraints of constraintFallbacks) {
                    try {
                        stream = await navigator.mediaDevices.getUserMedia(constraints);
                        break;
                    } catch (e) {
                        lastErr = e;
                    }
                }
                if (!stream) throw lastErr;
                this.stream = stream;

                const video = this.$refs.video;
                if (video) {
                    video.muted = true;
                    video.setAttribute('muted', '');
                    video.setAttribute('playsinline', true);
                    video.setAttribute('webkit-playsinline', true);
                    video.srcObject = stream;
                    video.load();
                    try {
                        await video.play();
                    } catch (playErr) {
                        this.cameraError = 'play() failed: ' + playErr.name + ' — ' + playErr.message;
                        console.warn('video.play() rejected:', playErr.name, playErr.message);
                    }
                }
            } catch (err) {
                this.cameraError = err.message || 'Unable to access camera. Please allow camera permissions.';
                this.stopCamera();
            }
        },

        async enableQrMode() {
            this.stopCamera();
            this.cameraError = null;
            this.capturedImage = null;
            this.isQrMode = true;
            this.qrError = null;
            this.qrIsLoading = true;
            this.isCameraOpen = true;

            try {
                const resp = await fetch('{{ route('phone-photo.create-session') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                    },
                });
                const data = await resp.json();
                if (!resp.ok || !data.success) {
                    throw new Error(data.message || 'Failed to create phone photo session.');
                }
                this.qrSessionToken = data.session_token;
                this.qrCaptureUrl = data.capture_url;
                this.qrDataUri = data.qr_data_uri;

                const expDate = new Date(data.expires_at);
                this.qrExpiresSeconds = Math.max(1, Math.floor((expDate - new Date()) / 1000));
                this.startQrExpireCountdown();
                this.startQrPolling();

            } catch (err) {
                console.error('QR session error:', err);
                this.qrError = err.message || 'Unable to create phone photo session. Please refresh and try again.';
            } finally {
                this.qrIsLoading = false;
            }
        },

        startQrPolling() {
            this.stopQrPolling();
            this.qrPollTimer = setInterval(async () => {
                if (!this.qrSessionToken) return;
                try {
                    const resp = await fetch('{{ url('phone-photo') }}/' + this.qrSessionToken + '/status', {
                        headers: { 'Accept': 'application/json' },
                    });
                    const data = await resp.json();
                    if (!data.success) {
                        this.qrError = data.message || 'Session error.';
                        this.stopQrPolling();
                        return;
                    }
                    if (data.status === 'completed' && data.photo_data) {
                        this.stopQrPolling();
                        this.capturedImage = data.photo_data;
                        this.isQrMode = false;
                        this.clearQrCountdown();
                    } else if (data.status === 'expired') {
                        this.stopQrPolling();
                        this.qrError = 'Session has expired. Please generate a new QR code.';
                    }
                } catch (e) {
                    // Network blip — keep polling silently
                }
            }, 2000);
        },

        stopQrPolling() {
            if (this.qrPollTimer) {
                clearInterval(this.qrPollTimer);
                this.qrPollTimer = null;
            }
        },

        startQrExpireCountdown() {
            this.clearQrCountdown();
            this.qrExpiresTimer = setInterval(() => {
                this.qrExpiresSeconds = Math.max(0, this.qrExpiresSeconds - 1);
                if (this.qrExpiresSeconds <= 0) {
                    this.clearQrCountdown();
                }
            }, 1000);
        },

        clearQrCountdown() {
            if (this.qrExpiresTimer) {
                clearInterval(this.qrExpiresTimer);
                this.qrExpiresTimer = null;
            }
        },

        formatQrCountdown() {
            const m = Math.floor(this.qrExpiresSeconds / 60);
            const s = this.qrExpiresSeconds % 60;
            return m + ':' + String(s).padStart(2, '0');
        },

        copyQrLink() {
            if (!this.qrCaptureUrl) return;
            navigator.clipboard?.writeText(this.qrCaptureUrl).catch(() => {});
        },

        switchFromQrToCamera() {
            this.stopQrPolling();
            this.clearQrCountdown();
            this.isQrMode = false;
            this.qrSessionToken = null;
            this.qrCaptureUrl = null;
            this.qrDataUri = null;
            this.qrError = null;
            this.openCamera();
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

            this.capturedImage = canvas.toDataURL('image/jpeg', 0.9);
            this.stopCamera();
            this.stopQrPolling();
        },

        retake() {
            this.capturedImage = null;
            if (this.isQrMode) {
                this.enableQrMode();
            } else {
                this.openCamera();
            }
        },

        confirm() {
            if (this.capturedImage) {
                this.state = this.capturedImage;
                this.isCameraOpen = false;
                this.stopCamera();
                this.stopQrPolling();
                this.clearQrCountdown();
            }
        },

        clearPhoto() {
            this.state = null;
            this.capturedImage = null;
            this.stopCamera();
            this.stopQrPolling();
            this.clearQrCountdown();
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
            this.stopQrPolling();
            this.clearQrCountdown();
            this.isQrMode = false;
            this.qrSessionToken = null;
            this.qrCaptureUrl = null;
            this.qrDataUri = null;
            this.qrError = null;
        },

        initSubmitGuard() {
            // Browser native HTML5 validation is intentionally disabled (novalidate is applied
            // by the script block below). All validation goes through Livewire + PHP so the
            // custom descriptive per-field messages and sequential auto-scroll work correctly.
            //
            // We keep a submit-button shake animation for tactile feedback only.
            const formEl = this.$el.closest('form');
            if (!formEl || formEl.dataset.clientValidationGuard) return;
            formEl.dataset.clientValidationGuard = '1';

            formEl.addEventListener('submit', () => {
                const submitBtn = formEl.querySelector('[type=submit]');
                submitBtn?.animate(
                    [
                        { transform: 'translateX(0)' },
                        { transform: 'translateX(-6px)' },
                        { transform: 'translateX(6px)' },
                        { transform: 'translateX(-4px)' },
                        { transform: 'translateX(4px)' },
                        { transform: 'translateX(0)' },
                    ],
                    { duration: 350, easing: 'ease-in-out' }
                );
            }, true);
        }
    }"
    x-init="initSubmitGuard()"
    id="field-wrap-live-photo"
    class="fi-fo-live-photo-capture space-y-2"
>
    <label class="flex items-center gap-1 text-sm font-medium text-gray-700 dark:text-gray-200">
        {{ $getLabel() }}
        @if ($isRequired())
            <span class="text-red-500">*</span>
        @endif
    </label>

    <canvas x-ref="canvas" class="hidden"></canvas>

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

    <template x-if="!state && !isCameraOpen">
        <div class="border-2 border-dashed {{ $errors->has($statePath) ? 'border-red-400 dark:border-red-700' : 'border-gray-300 dark:border-gray-700' }} rounded-2xl p-6 text-center bg-gray-50/50 dark:bg-gray-900/30 hover:border-teal-500 transition">
            <div class="w-14 h-14 mx-auto rounded-full bg-teal-50 dark:bg-teal-950/50 border border-teal-200 dark:border-teal-800 flex items-center justify-center text-teal-600 dark:text-teal-400 mb-3 shadow-xs">
                <x-filament::icon icon="heroicon-o-camera" class="w-7 h-7" />
            </div>
            <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Live Selfie Photo Required</h4>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                Photo must be taken directly via device camera. Gallery and file uploads are strictly restricted for verification security.
            </p>
            <div class="mt-5 flex flex-col sm:flex-row items-center justify-center gap-3">
                <button
                    type="button"
                    x-on:click="openCamera()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold text-white bg-teal-600 hover:bg-teal-700 focus:ring-4 focus:ring-teal-500/20 shadow-sm transition cursor-pointer"
                >
                    <x-filament::icon icon="heroicon-m-camera" class="w-4 h-4" />
                    <span>Open Webcam & Take Photo</span>
                </button>
                <button
                    type="button"
                    x-on:click="enableQrMode()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 focus:ring-4 focus:ring-teal-500/10 shadow-sm transition cursor-pointer"
                >
                    <x-filament::icon icon="heroicon-m-qr-code" class="w-4 h-4" />
                    <span>Use Phone Camera (QR)</span>
                </button>
            </div>

            <input
                type="text"
                :value="state ?? ''"
                required
                tabindex="-1"
                aria-hidden="true"
                class="opacity-0 h-0 w-full absolute pointer-events-none"
            />
        </div>
    </template>

    <div
        wire:ignore
        x-cloak
        x-show="isCameraOpen"
        x-transition.opacity
        class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/75 backdrop-blur-xs"
    >
        <div
            x-on:click.outside="closeModal()"
            class="bg-white dark:bg-gray-900 rounded-2xl shadow-2xl max-w-lg w-full overflow-hidden border border-gray-200 dark:border-gray-800"
        >
            <div class="px-5 py-4 border-b border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-m-camera" class="w-5 h-5 text-teal-600" />
                    <h3 class="text-sm font-bold text-gray-900 dark:text-white">Live Photo Capture</h3>
                </div>
                <div class="flex items-center gap-2">
                    <div class="flex rounded-lg bg-gray-100 dark:bg-gray-800 p-0.5 text-[10px] font-semibold">
                        <button
                            type="button"
                            x-show="capturedImage || !isQrMode"
                            x-on:click="capturedImage = null; openCamera()"
                            :class="!isQrMode && !capturedImage ? 'bg-white dark:bg-gray-700 text-teal-600 dark:text-teal-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                            class="px-2.5 py-1 rounded-md transition"
                        >
                            Webcam
                        </button>
                        <button
                            type="button"
                            x-on:click="capturedImage = null; enableQrMode()"
                            :class="isQrMode && !capturedImage ? 'bg-white dark:bg-gray-700 text-teal-600 dark:text-teal-400 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700'"
                            class="px-2.5 py-1 rounded-md transition"
                        >
                            Phone QR
                        </button>
                    </div>
                    <button
                        type="button"
                        x-on:click="closeModal()"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200"
                    >
                        <x-filament::icon icon="heroicon-m-x-mark" class="w-5 h-5" />
                    </button>
                </div>
            </div>

            <div class="p-5 space-y-4">
                <template x-if="cameraError">
                    <div class="p-4 rounded-xl bg-red-50 dark:bg-red-950/40 border border-red-200 dark:border-red-800 text-xs text-red-700 dark:text-red-300">
                        <p class="font-bold flex items-center gap-1.5">
                            <x-filament::icon icon="heroicon-m-exclamation-circle" class="w-4 h-4" />
                            Camera Access Error
                        </p>
                        <p class="mt-1" x-text="cameraError"></p>
                        <button
                            type="button"
                            x-on:click="enableQrMode()"
                            class="mt-3 w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-white dark:bg-red-950/60 border border-red-300 dark:border-red-800 text-red-700 dark:text-red-300 text-[11px] font-semibold hover:bg-red-50 transition"
                        >
                            <x-filament::icon icon="heroicon-m-qr-code" class="w-3.5 h-3.5" />
                            No webcam? Use your phone camera via QR code
                        </button>
                    </div>
                </template>

                <template x-if="isQrMode && !capturedImage && !qrIsLoading">
                    <div class="space-y-4">
                        <template x-if="qrError">
                            <div class="p-3 rounded-xl bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800 text-xs text-amber-700 dark:text-amber-300">
                                <p x-text="qrError"></p>
                            </div>
                        </template>

                        <div class="flex flex-col items-center justify-center py-3">
                            <div class="bg-white rounded-2xl p-4 border border-gray-200 dark:border-gray-700 shadow-md">
                                <img
                                    :src="qrDataUri"
                                    width="220"
                                    height="220"
                                    alt="Scan to open camera on phone"
                                    class="w-[220px] h-[220px]"
                                />
                            </div>

                            <div class="mt-4 text-center max-w-xs">
                                <h4 class="text-sm font-bold text-gray-900 dark:text-white flex items-center justify-center gap-1.5">
                                    <x-filament::icon icon="heroicon-m-qr-code" class="w-4 h-4 text-teal-600" />
                                    Scan with your phone
                                </h4>
                                <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-1">
                                    Open your phone camera, point it at the QR code, then tap the link to take a live selfie.
                                </p>
                            </div>

                            <div class="mt-4 w-full max-w-xs space-y-2">
                                <div class="flex items-center justify-between text-[11px]">
                                    <span class="text-gray-500 dark:text-gray-400">Session expires in</span>
                                    <span x-text="formatQrCountdown()" class="font-mono font-semibold text-teal-600 dark:text-teal-400"></span>
                                </div>
                                <div class="w-full h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-teal-500 transition-all"
                                         :style="'width: ' + Math.max(0, Math.min(100, (qrExpiresSeconds / 900) * 100)) + '%'"></div>
                                </div>

                                <button
                                    type="button"
                                    x-on:click="enableQrMode()"
                                    class="w-full mt-2 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-600 dark:text-gray-300 hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition"
                                >
                                    <x-filament::icon icon="heroicon-m-arrow-path" class="w-3.5 h-3.5" />
                                    Generate new QR
                                </button>

                                <button
                                    type="button"
                                    x-on:click="copyQrLink()"
                                    class="w-full inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-700 text-[11px] font-semibold text-gray-600 dark:text-gray-300 hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 transition"
                                >
                                    <x-filament::icon icon="heroicon-m-link" class="w-3.5 h-3.5" />
                                    Copy link instead
                                </button>
                            </div>

                            <div class="mt-5 flex items-center gap-2 text-[11px] text-gray-500 dark:text-gray-400 animate-pulse">
                                <span class="w-2 h-2 rounded-full bg-teal-500 animate-ping"></span>
                                <span>Waiting for photo from your phone...</span>
                            </div>
                        </div>
                    </div>
                </template>

                <template x-if="isQrMode && qrIsLoading && !capturedImage">
                    <div class="py-16 flex flex-col items-center justify-center">
                        <div class="w-12 h-12 border-4 border-teal-500/30 border-t-teal-500 rounded-full animate-spin mb-3"></div>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Generating secure QR session...</p>
                    </div>
                </template>

                <div x-show="!capturedImage && !cameraError && !isQrMode" class="relative aspect-4/3 w-full rounded-xl overflow-hidden bg-black shadow-inner flex items-center justify-center">
                    <video
                        x-ref="video"
                        autoplay
                        playsinline
                        webkit-playsinline
                        muted
                        class="w-full h-full object-cover transform -scale-x-100"
                    ></video>
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                        <div class="w-48 h-60 border-2 border-dashed border-white/70 rounded-full shadow-lg"></div>
                    </div>
                    <div class="absolute bottom-3 left-0 right-0 text-center pointer-events-none">
                        <span class="inline-block px-3 py-1 bg-black/60 backdrop-blur-xs text-white text-[11px] font-medium rounded-full">
                            Position your face within the frame
                        </span>
                    </div>
                </div>

                <div x-show="capturedImage" class="relative aspect-4/3 w-full rounded-xl overflow-hidden bg-black shadow-inner flex items-center justify-center">
                    <img :src="capturedImage" alt="Captured Selfie" class="w-full h-full object-cover transform -scale-x-100" />
                    <div class="absolute top-3 left-3 bg-teal-600 text-white text-[10px] font-bold px-2.5 py-1 rounded-full shadow-md flex items-center gap-1">
                        <x-filament::icon icon="heroicon-m-check" class="w-3 h-3" />
                        Photo Ready
                    </div>
                </div>
            </div>

            <div class="px-5 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-800 flex items-center justify-between">
                <template x-if="!capturedImage && !cameraError && !isQrMode">
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

                <template x-if="!capturedImage && !cameraError && isQrMode">
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
                            x-on:click="switchFromQrToCamera()"
                            class="inline-flex items-center gap-2 px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-300 border border-gray-300 dark:border-gray-700 hover:border-teal-500 hover:text-teal-600 dark:hover:text-teal-400 rounded-lg transition"
                        >
                            <x-filament::icon icon="heroicon-m-camera" class="w-3.5 h-3.5" />
                            Use Webcam Instead
                        </button>
                    </div>
                </template>

                <template x-if="cameraError && !capturedImage && !isQrMode">
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
                            x-on:click="openCamera()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 shadow-md transition cursor-pointer"
                        >
                            Retry Webcam
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

    @error($statePath)
        <div class="p-2.5 rounded-lg bg-red-50 dark:bg-red-950/30 border border-red-200 dark:border-red-900 text-xs text-red-700 dark:text-red-300 flex items-center gap-2">
            <x-filament::icon icon="heroicon-m-exclamation-triangle" class="w-4 h-4 text-red-600 dark:text-red-400 shrink-0" />
            <span>{{ $message }}</span>
        </div>
    @enderror
</div>

{{--
    Sequential scroll-to-first-error listener.
    Registered @once so it loads a single time even if Livewire re-renders this component.
    The PHP Register::dispatchScrollForFirstError() dispatches the 'scroll-to-first-error'
    browser event via Livewire's $this->dispatch(), which Livewire ships as a window CustomEvent.
--}}
@once
<script>
    (function () {
        if (window.__scrollToFirstErrorRegistered) return;
        window.__scrollToFirstErrorRegistered = true;

        // ── 1. Disable browser-native HTML5 validation on all Filament forms ──────
        // Removes "Please fill out this field." browser tooltips entirely.
        // All validation is handled by Livewire + PHP which shows our custom
        // descriptive per-field messages and sequential auto-scroll.
        function applyNoValidate() {
            document.querySelectorAll('form').forEach(function (f) {
                if (!f.hasAttribute('novalidate')) f.setAttribute('novalidate', '');
            });
        }
        applyNoValidate();
        // Re-apply after Livewire SPA navigation
        document.addEventListener('livewire:navigated', applyNoValidate);

        // ── 2. Sequential scroll-to-first-error listener ─────────────────────────
        // The PHP Register::dispatchScrollForFirstError() dispatches 'scroll-to-first-error'
        // as a Livewire browser event (window CustomEvent).
        window.addEventListener('scroll-to-first-error', function (e) {
            var fieldId = e.detail && e.detail.fieldId;
            if (!fieldId) return;

            // getElementById handles dots in id attributes correctly (unlike CSS querySelector).
            var el = document.getElementById(fieldId);
            if (!el) return;

            // Prefer scrolling to the field wrapper so the label + error message are both visible.
            var target =
                el.closest('.fi-fo-field-wrp') ||
                el.closest('.fi-section-content-ctn') ||
                el.closest('[id^="section-"]') ||
                el;

            target.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Focus the input after the scroll animation settles.
            setTimeout(function () {
                try {
                    var inp = (el.matches && el.matches('input, textarea, select'))
                        ? el
                        : (el.querySelector ? el.querySelector('input:not([type=hidden]), textarea, select') : null);
                    if (inp && !inp.disabled && !inp.readOnly) inp.focus();
                } catch (ex) { /* ignore */ }
            }, 500);
        });
    })();
</script>
@endonce
