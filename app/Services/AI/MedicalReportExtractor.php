<?php

namespace App\Services\AI;

use App\Models\AppointmentReport;
use App\Support\MedicalFile;
use Illuminate\Support\Facades\Storage;

class MedicalReportExtractor
{
    public const FILE_LIMIT = 4 * 1024 * 1024;

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

        if ($size <= 0 || $size > self::FILE_LIMIT) {
            return null;
        }

        $contents = Storage::disk($disk)->get($path);

        if (! is_string($contents) || $contents === '') {
            return null;
        }

        return [
            'mime' => $mime,
            'contents' => $contents,
        ];
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
