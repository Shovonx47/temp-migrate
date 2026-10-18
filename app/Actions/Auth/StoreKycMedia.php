<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class StoreKycMedia
{
    public function storeFile(UploadedFile $file, string $directory): string
    {
        return $file->store($directory, 'private');
    }

    public function storeLivePhoto(?string $dataUrl): ?string
    {
        if (! filled($dataUrl) || ! str_starts_with($dataUrl, 'data:image')) {
            return null;
        }

        $base64Image = preg_replace('#^data:image/\w+;base64,#i', '', $dataUrl) ?? '';
        $decoded = base64_decode($base64Image, true);

        if ($decoded === false || $decoded === '') {
            return null;
        }

        $filename = 'live_photos/live_'.Str::uuid().'.jpg';
        Storage::disk('private')->put($filename, $decoded);

        return $filename;
    }
}
