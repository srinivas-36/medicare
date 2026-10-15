<?php

namespace App\Services\AI;

use App\Models\AppointmentReport;
use App\Support\MedicalFile;
use Illuminate\Support\Facades\Storage;

class MedicalReportExtractor
{
    public const FILE_LIMIT = 8 * 1024 * 1024;

    public static function supported(AppointmentReport $report): bool
    {
        return self::mime((string) $report->original_name) !== null
            || self::mime((string) $report->path) !== null;
    }

    /**
     * @param  iterable<int, AppointmentReport>  $reports
     * @return list<array{index: int, report_id: int, label: string, group: string, status: string, mime: ?string, contents: ?string}>
     */
    public function extract(iterable $reports): array
    {
        $files = [];
        $index = 0;

        foreach ($reports as $report) {
            $index++;
            $mime = self::mime((string) $report->original_name) ?? self::mime((string) $report->path);
            $label = $this->label($report, $index);
            $group = trim((string) $report->group_name) ?: 'Report';
            $opened = $mime === null ? null : $this->contents($report, $mime);

            $files[] = [
                'index' => $index,
                'report_id' => (int) $report->id,
                'label' => $label,
                'group' => $group,
                'status' => $opened === null ? 'unreadable' : 'attached',
                'mime' => $opened['mime'] ?? null,
                'contents' => $opened['contents'] ?? null,
            ];
        }

        return $files;
    }

    /**
     * @return array{mime: string, contents: string}|null
     */
    private function contents(AppointmentReport $report, string $mime): ?array
    {
        $path = (string) $report->path;

        if ($path === '' || str_contains($path, '..') || ! MedicalFile::exists($path)) {
            return null;
        }

        $disk = Storage::disk(MedicalFile::DISK)->exists($path) ? MedicalFile::DISK : 'public';
        $size = Storage::disk($disk)->size($path);

        if ($size <= 0) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        if ($size > self::FILE_LIMIT) {
            if (! str_starts_with($mime, 'image/')) {
                return null;
            }

            $shrunk = $this->shrink($contents);

            if ($shrunk === null) {
                return null;
            }

            return [
                'mime' => 'image/jpeg',
                'contents' => $shrunk,
            ];
        }

        return [
            'mime' => $mime,
            'contents' => $contents,
        ];
    }

    private function shrink(string $contents): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $image = @imagecreatefromstring($contents);

        if ($image === false) {
            return null;
        }

        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);
        $limit = 1800;

        if ($longest > $limit) {
            $scale = $limit / $longest;
            $target = imagecreatetruecolor(max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)));

            if ($target === false) {
                imagedestroy($image);

                return null;
            }

            imagecopyresampled($target, $image, 0, 0, 0, 0, imagesx($target), imagesy($target), $width, $height);
            imagedestroy($image);
            $image = $target;
        }

        ob_start();
        $written = imagejpeg($image, null, 75);
        $jpeg = ob_get_clean();
        imagedestroy($image);

        if ($written !== true || ! is_string($jpeg) || $jpeg === '' || strlen($jpeg) > self::FILE_LIMIT) {
            return null;
        }

        return $jpeg;
    }

    public static function mime(string $filename): ?string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'pdf' => 'application/pdf',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'txt' => 'text/plain',
            default => null,
        };
    }

    private function label(AppointmentReport $report, int $number): string
    {
        $name = trim((string) $report->original_name);
        $base = pathinfo($name, PATHINFO_FILENAME);
        $machine = $name === ''
            || preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $base) === 1
            || preg_match('/^[A-Za-z0-9]{32,}$/', $base) === 1;

        if ($machine) {
            $group = trim((string) $report->group_name) ?: 'Report';

            return $number > 1 ? $group.' '.$number : $group;
        }

        return $name;
    }
}
