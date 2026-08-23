<?php

namespace App\Services;

class SimplePdfReportBuilder
{
    /**
     * @param  array<int, string>  $columns
     * @param  array<int, array<int, string>>  $rows
     * @param  array<string, string>  $filters
     */
    public function build(string $title, array $columns, array $rows, array $filters = []): string
    {
        $lines = [];
        $lines[] = $title;
        $lines[] = 'Dicetak: ' . now()->format('d M Y H:i');

        if ($filters !== []) {
            $lines[] = 'Filter aktif:';

            foreach ($filters as $label => $value) {
                $lines = [...$lines, ...$this->wrapLine("- {$label}: {$value}", 92)];
            }
        }

        $lines[] = '';
        $lines[] = implode(' | ', $columns);
        $lines[] = str_repeat('-', 92);

        foreach ($rows as $row) {
            $rowLine = implode(' | ', $row);

            foreach ($this->wrapLine($rowLine, 92) as $wrappedLine) {
                $lines[] = $wrappedLine;
            }

            $lines[] = '';
        }

        return $this->renderPdf($title, $lines);
    }

    /**
     * @param  array<int, string>  $lines
     */
    protected function renderPdf(string $title, array $lines): string
    {
        $pageWidth = 595;
        $pageHeight = 842;
        $left = 40;
        $top = 800;
        $lineHeight = 14;
        $linesPerPage = 48;
        $fontObjectId = 1;

        $pages = array_chunk($lines, $linesPerPage);
        $objects = [];
        $objects[$fontObjectId] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";

        $pageObjectIds = [];
        $contentObjectIds = [];
        $nextObjectId = 2;

        foreach ($pages as $pageIndex => $pageLines) {
            $contentObjectId = $nextObjectId++;
            $pageObjectId = $nextObjectId++;

            $streamLines = ['BT', '/F1 11 Tf'];
            $y = $top;

            if ($pageIndex > 0) {
                $pageLines = array_merge(
                    [$title . ' (lanjutan)', ''],
                    $pageLines,
                );
            }

            foreach ($pageLines as $line) {
                $escaped = $this->escapePdfText($line);
                $streamLines[] = sprintf('1 0 0 1 %d %d Tm (%s) Tj', $left, $y, $escaped);
                $y -= $lineHeight;
            }

            $streamLines[] = 'ET';
            $stream = implode("\n", $streamLines);
            $objects[$contentObjectId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageObjectId] = "<< /Type /Page /Parent PAGES_ID 0 R /MediaBox [0 0 {$pageWidth} {$pageHeight}] /Contents {$contentObjectId} 0 R /Resources << /Font << /F1 {$fontObjectId} 0 R >> >> >>";

            $pageObjectIds[] = $pageObjectId;
            $contentObjectIds[] = $contentObjectId;
        }

        $pagesObjectId = $nextObjectId++;
        $catalogObjectId = $nextObjectId++;
        $infoObjectId = $nextObjectId++;

        $kids = implode(' ', array_map(fn (int $id): string => "{$id} 0 R", $pageObjectIds));
        $objects[$pagesObjectId] = "<< /Type /Pages /Kids [ {$kids} ] /Count " . count($pageObjectIds) . " >>";

        foreach ($pageObjectIds as $pageObjectId) {
            $objects[$pageObjectId] = str_replace('PAGES_ID', (string) $pagesObjectId, $objects[$pageObjectId]);
        }

        $objects[$catalogObjectId] = "<< /Type /Catalog /Pages {$pagesObjectId} 0 R >>";
        $objects[$infoObjectId] = "<< /Title (" . $this->escapePdfText($title) . ") /Producer (Core Farmasi UBP) >>";

        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $content) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$content}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxObjectId = max(array_keys($objects));

        $pdf .= "xref\n0 " . ($maxObjectId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id <= $maxObjectId; $id++) {
            $offset = $offsets[$id] ?? 0;
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }

        $pdf .= "trailer\n<< /Size " . ($maxObjectId + 1) . " /Root {$catalogObjectId} 0 R /Info {$infoObjectId} 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    protected function escapePdfText(string $text): string
    {
        return str_replace(
            ['\\', '(', ')', "\r", "\n", "\t"],
            ['\\\\', '\(', '\)', ' ', ' ', ' '],
            $text,
        );
    }

    /**
     * @return array<int, string>
     */
    protected function wrapLine(string $text, int $width): array
    {
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);

        if ($text === '') {
            return [''];
        }

        return explode("\n", wordwrap($text, $width, "\n", true));
    }
}
