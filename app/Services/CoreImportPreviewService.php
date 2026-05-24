<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class CoreImportPreviewService
{
    public function __construct(
        protected CoreImportTemplateService $templates,
    ) {}

    public function preview(string $importType, string $filePath, ?string $originalFilename = null, ?string $storedPath = null): array
    {
        $definition = $this->templates->type($importType);
        $previewLimit = (int) config('core_import.upload.preview_limit', 10);

        $result = [
            'import_type' => $importType,
            'filename' => basename($filePath),
            'original_filename' => $originalFilename,
            'stored_path' => $storedPath,
            'headings' => [],
            'missing_required_columns' => [],
            'unknown_columns' => [],
            'password_columns' => [],
            'preview_rows' => [],
            'row_count_estimate' => 0,
            'errors' => [],
            'warnings' => [],
            'status' => 'failed',
            'is_valid_for_preview' => false,
        ];

        try {
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = $sheet->getHighestDataRow();
            $highestColumn = $sheet->getHighestDataColumn();

            $rawHeadingRow = $sheet->rangeToArray("A1:{$highestColumn}1", null, true, false)[0] ?? [];
            $headings = $this->normalizeHeadings($rawHeadingRow);

            $expectedColumns = [
                ...($definition['required_columns'] ?? []),
                ...($definition['optional_columns'] ?? []),
            ];

            $passwordColumns = array_values(array_filter(
                $headings,
                fn (string $heading): bool => in_array($heading, ['password', 'password_confirmation'], true),
            ));

            $missingRequired = array_values(array_diff($definition['required_columns'] ?? [], $headings));
            $unknownColumns = array_values(array_diff($headings, $expectedColumns));

            $result['headings'] = $headings;
            $result['missing_required_columns'] = $missingRequired;
            $result['unknown_columns'] = $unknownColumns;
            $result['password_columns'] = $passwordColumns;
            $result['row_count_estimate'] = max(0, $highestRow - 1);

            if ($headings === []) {
                $result['errors'][] = 'Heading row tidak ditemukan.';
            }

            if ($missingRequired !== []) {
                $result['errors'][] = 'Required columns belum lengkap.';
            }

            if ($passwordColumns !== []) {
                $result['errors'][] = 'Kolom password tidak diperbolehkan.';
                $result['warnings'][] = 'Kolom password akan diabaikan dan tidak ditampilkan di preview.';
            }

            if ($unknownColumns !== []) {
                $result['warnings'][] = 'File memiliki kolom tambahan yang belum dikenal.';
            }

            $result['preview_rows'] = $this->previewRows($sheet, $headings, $highestColumn, $highestRow, $previewLimit);
            $result['is_valid_for_preview'] = $headings !== [] && $missingRequired === [] && $passwordColumns === [];
            $result['status'] = $result['is_valid_for_preview'] ? 'preview_ready' : 'invalid_heading';
        } catch (Throwable $exception) {
            $result['errors'][] = 'File tidak dapat dibaca sebagai spreadsheet.';
            $result['warnings'][] = $exception->getMessage();
        }

        return $result;
    }

    public function normalizeHeading(?string $heading): string
    {
        return str((string) $heading)
            ->trim()
            ->lower()
            ->replace([' ', '-', '.'], '_')
            ->replaceMatches('/[^a-z0-9_]/', '')
            ->replaceMatches('/_+/', '_')
            ->trim('_')
            ->toString();
    }

    protected function normalizeHeadings(array $headings): array
    {
        return collect($headings)
            ->map(fn (mixed $heading): string => $this->normalizeHeading(is_scalar($heading) ? (string) $heading : null))
            ->filter()
            ->values()
            ->all();
    }

    protected function previewRows($sheet, array $headings, string $highestColumn, int $highestRow, int $previewLimit): array
    {
        if ($headings === [] || $highestRow < 2) {
            return [];
        }

        $lastRow = min($highestRow, $previewLimit + 1);
        $rows = $sheet->rangeToArray("A2:{$highestColumn}{$lastRow}", null, true, false);

        return collect($rows)
            ->map(function (array $row) use ($headings): array {
                $mapped = [];

                foreach ($headings as $index => $heading) {
                    if (in_array($heading, ['password', 'password_confirmation'], true)) {
                        continue;
                    }

                    $mapped[$heading] = $row[$index] ?? null;
                }

                return $mapped;
            })
            ->values()
            ->all();
    }
}
