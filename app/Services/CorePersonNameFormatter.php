<?php

namespace App\Services;

class CorePersonNameFormatter
{
    public function normalizePersonName(?string $name): ?string
    {
        $name = trim((string) $name);

        if ($name === '') {
            return null;
        }

        $name = preg_replace('/\s+/', ' ', $name) ?: $name;
        $lower = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);

        $normalized = function_exists('mb_convert_case')
            ? mb_convert_case($lower, MB_CASE_TITLE, 'UTF-8')
            : ucwords($lower);

        return $this->preserveKnownNameAcronyms($normalized);
    }

    public function preserveKnownNameAcronyms(string $name): string
    {
        $acronyms = ['API', 'QA', 'UBP'];

        foreach ($acronyms as $acronym) {
            $name = preg_replace('/\b'.preg_quote(ucfirst(strtolower($acronym)), '/').'\b/u', $acronym, $name) ?? $name;
        }

        return $name;
    }

    public function formatWithTitle(?string $frontTitle, ?string $name, ?string $backTitle): string
    {
        $frontTitle = $this->normalizeTitle($frontTitle);
        $name = $this->normalizeTitle($name);
        $backTitle = $this->normalizeTitle($backTitle);

        $formatted = trim(implode(' ', array_filter([$frontTitle, $name])));

        if ($backTitle) {
            $formatted = rtrim($formatted, " \t\n\r\0\x0B,").', '.$backTitle;
        }

        return trim(preg_replace('/\s+/', ' ', $formatted) ?? '');
    }

    public function normalizeTitle(?string $title): ?string
    {
        $title = trim((string) $title);

        if ($title === '') {
            return null;
        }

        return preg_replace('/\s+/', ' ', $title) ?: null;
    }
}
