<?php

namespace App\Exports;

use App\Services\CoreImportTemplateService;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class CoreImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings, WithTitle
{
    public function __construct(
        protected string $type,
        protected CoreImportTemplateService $templates,
    ) {}

    public function headings(): array
    {
        return $this->templates->headings($this->type);
    }

    public function array(): array
    {
        return $this->templates->rows($this->type);
    }

    public function title(): string
    {
        return str($this->type)->replace('_', ' ')->title()->limit(31, '')->toString();
    }
}
