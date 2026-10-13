<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class ProfilePhoto
{
    /**
     * Store an upload as photos/{role}/{YmdHis}.ext on the public disk.
     * The returned path is what the views pass to asset(), for example
     * storage/photos/patient/20261011184922.jpg.
     */
    public static function store(?UploadedFile $file, ?string $role = null): ?string
    {
        if ($file === null) {
            return null;
        }

        $directory = 'photos/'.self::roleFolder($role);
        $extension = self::extension($file);
        $basename = now()->format('YmdHis');
        $filename = $basename.'.'.$extension;
        $suffix = 1;

        while (Storage::disk('public')->exists($directory.'/'.$filename)) {
            $filename = $basename.'_'.$suffix.'.'.$extension;
            $suffix++;
        }

        return 'storage/'.$file->storeAs($directory, $filename, 'public');
    }

    public static function delete(?string $path): void
    {
        if (! is_string($path) || $path === '' || str_starts_with($path, 'http')) {
            return;
        }

        $relative = str_starts_with($path, 'storage/')
            ? substr($path, strlen('storage/'))
            : $path;

        Storage::disk('public')->delete($relative);
    }

    private static function roleFolder(?string $role): string
    {
        $role = strtolower((string) $role);

        return in_array($role, ['patient', 'doctor', 'receptionist', 'admin'], true)
            ? $role
            : 'user';
    }

    private static function extension(UploadedFile $file): string
    {
        $extension = strtolower($file->getClientOriginalExtension());

        if (! in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            $extension = strtolower((string) $file->guessExtension());
        }

        return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)
            ? $extension
            : 'jpg';
    }
}
