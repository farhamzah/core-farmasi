<?php

namespace App\Services;

class CorePersonNameFormatter
{
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
