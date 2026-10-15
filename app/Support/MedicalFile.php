<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MedicalFile
{
    public const DISK = 'local';

    public static function put(string $path, string $contents): void
    {
        Storage::disk(self::DISK)->put($path, $contents);
    }

    public static function exists(string $path): bool
    {
        return Storage::disk(self::DISK)->exists($path)
            || Storage::disk('public')->exists($path);
    }

    public static function size(string $path): ?int
    {
        foreach ([self::DISK, 'public'] as $disk) {
            if (Storage::disk($disk)->exists($path)) {
                return Storage::disk($disk)->size($path);
            }
        }

        return null;
    }

    public static function delete(string $path): void
    {
        Storage::disk(self::DISK)->delete($path);
        Storage::disk('public')->delete($path);
    }

    public static function response(string $path, ?string $name = null): StreamedResponse
    {
        return self::stream($path, $name, false);
    }

    public static function download(string $path, ?string $name = null): StreamedResponse
    {
        return self::stream($path, $name, true);
    }

    private static function stream(string $path, ?string $name, bool $download): StreamedResponse
    {
        foreach ([self::DISK, 'public'] as $disk) {
            if (! Storage::disk($disk)->exists($path)) {
                continue;
            }

            return $download
                ? Storage::disk($disk)->download($path, $name)
                : Storage::disk($disk)->response($path, $name);
        }

        abort(404);
    }
}
