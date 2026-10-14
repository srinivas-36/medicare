<?php

namespace App\Services;

use App\Support\MedicalFile;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AppointmentReports
{
    /**
     * @return list<array{group_name: string, path: string, original_name: string}>
     */
    public function store(Request $request, int $patientId, string $date): array
    {
        $stored = [];

        foreach ([
            'blood_reports' => ['blood_report_name', 'Blood Test Reports'],
            'xray_reports' => ['xray_report_name', 'X-Ray Scans'],
        ] as $field => [$nameField, $fallback]) {
            $name = $this->groupName($request, $nameField, $fallback);

            foreach ($request->file($field, []) as $file) {
                if ($file instanceof UploadedFile) {
                    $stored[] = $this->keep($file, $patientId, $date, $name);
                }
            }
        }

        $otherName = trim((string) $request->input('other_report_name'));

        foreach ($request->file('other_reports', []) as $file) {
            if (! $file instanceof UploadedFile) {
                continue;
            }

            if ($otherName === '') {
                $this->discard($stored);

                throw ValidationException::withMessages([
                    'other_report_name' => 'Name the other report group before confirming.',
                ]);
            }

            $stored[] = $this->keep($file, $patientId, $date, $otherName);
        }

        return $stored;
    }

    /**
     * @param  list<array{group_name: string, path: string, original_name: string}>  $reports
     */
    public function discard(array $reports): void
    {
        $paths = array_values(array_filter(array_column($reports, 'path')));

        foreach ($paths as $path) {
            MedicalFile::delete($path);
        }
    }

    private function groupName(Request $request, string $field, string $fallback): string
    {
        $name = trim((string) $request->input($field));

        return $name !== '' ? $name : $fallback;
    }

    /**
     * @return array{group_name: string, path: string, original_name: string}
     */
    private function keep(UploadedFile $file, int $patientId, string $date, string $group): array
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $path = 'appointment-reports/'.$patientId.'/'.$date.'/'.Str::uuid()->toString().'.'.$extension;
        MedicalFile::put($path, (string) $file->get());

        return [
            'group_name' => $group,
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
        ];
    }
}
