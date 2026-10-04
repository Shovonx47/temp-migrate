<?php
$file = "resources/views/filament/forms/components/live-photo-capture.blade.php";
$content = file_get_contents($file);
$applied = [];
$skipped = [];

// 1) Remove debug state properties
$old1 = "        cameraError: null,
        isProcessing: false,
        videoDebugInfo: null,
        videoDebugTimer: null,";
$new1 = "        cameraError: null,
        isProcessing: false,";
if (strpos($content, $new1) !== false && strpos($content, $old1) === false) {
    $skipped[] = "1 (already clean)";
} elseif (strpos($content, $old1) === false) {
    echo "PATCH 1: OLD BLOCK NOT FOUND\n";
    exit(1);
} else {
    $content = str_replace($old1, $new1, $content);
    $applied[] = "1";
}

// 2) Remove the debug polling timer, keep the real play() error handling
$old2 = "                    try {
                        await video.play();
                    } catch (playErr) {
                        this.cameraError = 'play() failed: ' + playErr.name + ' — ' + playErr.message;
                        console.warn('video.play() rejected:', playErr.name, playErr.message);
                    }

                    if (this.videoDebugTimer) clearInterval(this.videoDebugTimer);
                    this.videoDebugTimer = setInterval(() => {
                        const track = this.stream ? this.stream.getVideoTracks()[0] : null;
                        this.videoDebugInfo = 'w:' + video.videoWidth + ' h:' + video.videoHeight +
                            ' ready:' + video.readyState +
                            ' paused:' + video.paused +
                            ' track:' + (track ? track.readyState : 'none');
                    }, 500);
                }";
$new2 = "                    try {
                        await video.play();
                    } catch (playErr) {
                        this.cameraError = 'play() failed: ' + playErr.name + ' — ' + playErr.message;
                        console.warn('video.play() rejected:', playErr.name, playErr.message);
                    }
                }";
if (strpos($content, $new2) !== false && strpos($content, $old2) === false) {
    $skipped[] = "2 (already clean)";
} elseif (strpos($content, $old2) === false) {
    echo "PATCH 2: OLD BLOCK NOT FOUND\n";
    exit(1);
} else {
    $content = str_replace($old2, $new2, $content);
    $applied[] = "2";
}

// 3) Template: remove debug span, restore mirror transform on live video
$old3 = '                <div x-show="!capturedImage && !cameraError && !isQrMode" class="relative aspect-4/3 w-full rounded-xl overflow-hidden bg-black shadow-inner flex items-center justify-center">
                    <video
                        x-ref="video"
                        autoplay
                        playsinline
                        webkit-playsinline
                        muted
                        class="w-full h-full object-cover"
                    ></video>
                    <div class="absolute inset-0 pointer-events-none flex items-center justify-center">
                        <div class="w-48 h-60 border-2 border-dashed border-white/70 rounded-full shadow-lg"></div>
                    </div>
                    <div class="absolute top-2 left-2 right-2 text-center pointer-events-none">
                        <span x-text="videoDebugInfo" class="inline-block px-2 py-1 bg-red-600/80 text-white text-[10px] font-mono rounded"></span>
                    </div>
                    <div class="absolute bottom-3 left-0 right-0 text-center pointer-events-none">
                        <span class="inline-block px-3 py-1 bg-black/60 backdrop-blur-xs text-white text-[11px] font-medium rounded-full">
                            Position your face within the frame
                        </span>
                    </div>
                </div>';
$new3 = '                <div x-show="!capturedImage && !cameraError && !isQrMode" class="relative aspect-4/3 w-full rounded-xl overflow-hidden bg-black shadow-inner flex items-center justify-center">
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
                </div>';
if (strpos($content, $new3) !== false && strpos($content, $old3) === false) {
    $skipped[] = "3 (already clean)";
} elseif (strpos($content, $old3) === false) {
    echo "PATCH 3: OLD BLOCK NOT FOUND\n";
    exit(1);
} else {
    $content = str_replace($old3, $new3, $content);
    $applied[] = "3";
}

file_put_contents($file, $content);
echo "Applied: " . (empty($applied) ? "none" : implode(",", $applied)) . "\n";
echo "Skipped: " . (empty($skipped) ? "none" : implode(",", $skipped)) . "\n";
