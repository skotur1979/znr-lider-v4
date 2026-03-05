<?php

namespace App\Support;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class SignatureStorage
{
    public static function storeDataUrl(?string $dataUrl, string $dir = 'ozo-signatures'): ?string
    {
        if (blank($dataUrl)) return null;

        // očekujemo "data:image/png;base64,...."
        if (! str_starts_with($dataUrl, 'data:image')) {
            // već je path
            return $dataUrl;
        }

        if (! preg_match('/^data:image\/(\w+);base64,/', $dataUrl, $m)) {
            return null;
        }

        $ext = strtolower($m[1] ?? 'png');
        $ext = in_array($ext, ['png', 'jpg', 'jpeg', 'webp'], true) ? $ext : 'png';

        $base64 = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($base64);

        if ($binary === false) return null;

        $name = Str::uuid()->toString() . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
        $path = trim($dir, '/') . '/' . $name;

        Storage::disk('public')->put($path, $binary);

        return $path;
    }
}