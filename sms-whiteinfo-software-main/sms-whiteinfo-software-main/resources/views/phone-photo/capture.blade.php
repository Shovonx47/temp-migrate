<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Take Live Photo - Phone Camera</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            overscroll-behavior: none;
            -webkit-tap-highlight-color: transparent;
        }
        video {
            -webkit-transform: scaleX(-1);
            transform: scaleX(-1);
        }
        .face-frame {
            box-shadow: 0 0 0 9999px rgba(0,0,0,0.4);
        }
    </style>
</head>
<body class="bg-gray-900 min-h-screen text-white">
    <div class="min-h-screen flex flex-col">
        <!-- Header -->
        <header class="bg-gray-800 border-b border-gray-700 px-4 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                <h1 class="text-sm font-semibold">Live Photo Capture</h1>
            </div>
            <div class="text-xs text-gray-400">SMSPro KYC</div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col" x-data="phoneCapture()" x-init="init()">
            <!-- Step: Camera Permission & Loading -->
            <div x-show="loading && !cameraError" class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                <div class="w-16 h-16 border-4 border-teal-500/30 border-t-teal-500 rounded-full animate-spin mb-4"></div>
                <p class="text-gray-300 text-sm">Starting camera...</p>
                <p class="text-gray-500 text-xs mt-2">Please allow camera permissions when prompted.</p>
            </div>

            <!-- Step: Camera Error -->
            <div x-show="cameraError" class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-red-500/20 border border-red-500/30 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Camera Error</h3>
                <p class="text-gray-400 text-sm mb-6" x-text="cameraError"></p>
                <button
                    @click="tryInitCamera()"
                    class="px-6 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl transition active:scale-95"
                >
                    Try Again
                </button>
            </div>

            <!-- Step: Camera Viewfinder -->
            <div x-show="!loading && !cameraError && !capturedImage && !uploadSuccess" class="flex-1 flex flex-col relative">
                <div class="flex-1 relative bg-black overflow-hidden">
                    <video
                        x-ref="video"
                        autoplay
                        playsinline
                        webkit-playsinline
                        muted
                        class="w-full h-full object-cover"
                    ></video>
                    <!-- Face Guide Oval -->
                    <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                        <div class="w-48 h-60 sm:w-56 sm:h-72 border-2 border-dashed border-white/70 rounded-full face-frame"></div>
                    </div>
                    <!-- Position Hint -->
                    <div class="absolute bottom-6 left-0 right-0 text-center pointer-events-none">
                        <span class="inline-block px-4 py-2 bg-black/60 backdrop-blur-sm text-white text-xs font-medium rounded-full">
                            Position your face within the frame
                        </span>
                    </div>
                </div>

                <!-- Capture Controls -->
                <div class="bg-gray-800 border-t border-gray-700 p-6 flex items-center justify-center">
                    <button
                        @click="takeSnapshot()"
                        class="w-20 h-20 rounded-full bg-white border-4 border-gray-600 hover:border-teal-500 transition active:scale-95 flex items-center justify-center shadow-lg"
                    >
                        <div class="w-14 h-14 rounded-full bg-red-500"></div>
                    </button>
                </div>
            </div>

            <!-- Step: Captured Preview -->
            <div x-show="capturedImage && !uploading && !uploadSuccess" class="flex-1 flex flex-col relative">
                <div class="flex-1 relative bg-black overflow-hidden flex items-center justify-center">
                    <img
                        :src="capturedImage"
                        alt="Captured Photo"
                        class="max-w-full max-h-full object-contain"
                        style="-webkit-transform: scaleX(-1); transform: scaleX(-1);"
                    />
                </div>

                <!-- Preview Controls -->
                <div class="bg-gray-800 border-t border-gray-700 p-4 flex items-center justify-between gap-3">
                    <button
                        @click="retake()"
                        class="flex-1 py-3.5 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition active:scale-95 flex items-center justify-center gap-2"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Retake
                    </button>
                    <button
                        @click="uploadPhoto()"
                        class="flex-1 py-3.5 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl transition active:scale-95 flex items-center justify-center gap-2 shadow-lg"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Use This Photo
                    </button>
                </div>
            </div>

            <!-- Step: Uploading -->
            <div x-show="uploading" class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                <div class="w-16 h-16 border-4 border-teal-500/30 border-t-teal-500 rounded-full animate-spin mb-4"></div>
                <p class="text-gray-300 text-sm font-medium">Uploading photo...</p>
            </div>

            <!-- Step: Upload Success -->
            <div x-show="uploadSuccess && !uploadError" class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                <div class="w-20 h-20 rounded-full bg-teal-500/20 border-2 border-teal-500 flex items-center justify-center mb-5">
                    <svg class="w-10 h-10 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-bold text-white mb-2">Photo Submitted!</h3>
                <p class="text-gray-400 text-sm mb-6 max-w-xs">
                    Your photo has been received. You may now close this page and return to the registration form on your computer.
                </p>
                <div class="bg-gray-800 border border-gray-700 rounded-xl p-4 max-w-xs w-full">
                    <p class="text-xs text-gray-500 mb-1">Captured at</p>
                    <p class="text-sm font-medium text-gray-300" x-text="completionTime"></p>
                </div>
            </div>

            <!-- Step: Upload Error -->
            <div x-show="uploadError" class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-red-500/20 border border-red-500/30 flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-white mb-2">Upload Failed</h3>
                <p class="text-gray-400 text-sm mb-6" x-text="uploadError"></p>
                <div class="flex gap-3 w-full max-w-xs">
                    <button
                        @click="uploadPhoto()"
                        class="flex-1 py-3 bg-teal-600 hover:bg-teal-700 text-white font-semibold rounded-xl transition active:scale-95"
                    >
                        Retry Upload
                    </button>
                    <button
                        @click="uploadError = null; retake()"
                        class="flex-1 py-3 bg-gray-700 hover:bg-gray-600 text-white font-semibold rounded-xl transition active:scale-95"
                    >
                        New Photo
                    </button>
                </div>
            </div>
        </main>
    </div>

    <canvas id="captureCanvas" class="hidden"></canvas>

    <script>
        function phoneCapture() {
            return {
                loading: true,
                stream: null,
                capturedImage: null,
                cameraError: null,
                uploading: false,
                uploadSuccess: false,
                uploadError: null,
                completionTime: '',

                init() {
                    this.tryInitCamera();
                },

                async tryInitCamera() {
                    this.loading = true;
                    this.cameraError = null;
                    this.stopCamera();

                    await new Promise(r => setTimeout(r, 300));

                    try {
                        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                            throw new Error('Camera is not supported on this browser. Please use a modern browser on your phone.');
                        }

                        // iOS Safari requires simplified constraints — try progressively simpler configs
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
                            video.srcObject = stream;
                            // iOS Safari requires these set programmatically
                            video.setAttribute('playsinline', true);
                            video.setAttribute('webkit-playsinline', true);
                            video.muted = true;
                            try {
                                await video.play();
                            } catch (playErr) {
                                console.warn('video.play() rejected:', playErr.message);
                            }
                        }

                        this.loading = false;
                    } catch (err) {
                        console.error('Camera error:', err);
                        this.cameraError = err.message || 'Unable to access camera. Please make sure camera permissions are enabled.';
                        this.loading = false;
                        this.stopCamera();
                    }
                },

                stopCamera() {
                    if (this.stream) {
                        this.stream.getTracks().forEach(track => track.stop());
                        this.stream = null;
                    }
                },

                takeSnapshot() {
                    const video = this.$refs.video;
                    const canvas = document.getElementById('captureCanvas');
                    if (!video || !canvas) return;

                    const width = video.videoWidth || 640;
                    const height = video.videoHeight || 480;

                    canvas.width = width;
                    canvas.height = height;

                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0, width, height);

                    this.capturedImage = canvas.toDataURL('image/jpeg', 0.9);
                    this.stopCamera();
                },

                retake() {
                    this.capturedImage = null;
                    this.tryInitCamera();
                },

                async uploadPhoto() {
                    this.uploading = true;
                    this.uploadError = null;

                    try {
                        const response = await fetch(@json($uploadUrl), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({
                                photo: this.capturedImage,
                            }),
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            this.uploadSuccess = true;
                            const now = new Date();
                            this.completionTime = now.toLocaleString();
                        } else {
                            throw new Error(data.message || 'Failed to upload photo.');
                        }
                    } catch (err) {
                        console.error('Upload error:', err);
                        this.uploadError = err.message || 'Network error. Please check your connection and try again.';
                    } finally {
                        this.uploading = false;
                    }
                },
            }
        }
    </script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</body>
</html>
