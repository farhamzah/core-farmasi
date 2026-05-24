<?php

namespace App\Services;

use InvalidArgumentException;

class CoreImportTemplateService
{
    public function enabledTypes(): array
    {
        return collect(config('core_import.types', []))
            ->filter(fn (array $type): bool => (bool) ($type['is_enabled'] ?? false))
            ->all();
    }

    public function type(string $type): array
    {
        $definition = config("core_import.types.{$type}");

        if (! is_array($definition) || ! ($definition['is_enabled'] ?? false)) {
            throw new InvalidArgumentException("Import type [{$type}] is not available.");
        }

        return $definition;
    }

    public function headings(string $type): array
    {
        $definition = $this->type($type);

        return [
            ...($definition['required_columns'] ?? []),
            ...($definition['optional_columns'] ?? []),
        ];
    }

    public function rows(string $type): array
    {
        $definition = $this->type($type);
        $headings = $this->headings($type);

        $rows = $definition['sample_rows'] ?? [];

        if ($rows === []) {
            return [array_fill(0, count($headings), null)];
        }

        return array_map(
            fn (array $row): array => array_pad(array_slice($row, 0, count($headings)), count($headings), null),
            $rows,
        );
    }

    public function filename(string $type): string
    {
        return $this->type($type)['template_filename'];
    }

    public function assertNoPasswordColumns(string $type): bool
    {
        $columns = array_map('strtolower', $this->headings($type));

        $prohibited = ['password', 'password_confirmation'];

        return ! collect($columns)->contains(fn (string $column): bool => in_array($column, $prohibited, true));
    }
}
