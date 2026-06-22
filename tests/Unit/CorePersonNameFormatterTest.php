<?php

namespace Tests\Unit;

use App\Services\CorePersonNameFormatter;
use PHPUnit\Framework\TestCase;

class CorePersonNameFormatterTest extends TestCase
{
    public function test_it_formats_front_and_back_titles(): void
    {
        $formatter = new CorePersonNameFormatter();

        $this->assertSame(
            'Dr. Farhamzah, M.Farm.',
            $formatter->formatWithTitle(' Dr. ', ' Farhamzah ', ' M.Farm. ')
        );
    }

    public function test_it_keeps_plain_name_when_titles_are_empty(): void
    {
        $formatter = new CorePersonNameFormatter();

        $this->assertSame('Farhamzah', $formatter->formatWithTitle(null, 'Farhamzah', null));
        $this->assertSame('Farhamzah, M.Farm.', $formatter->formatWithTitle('', 'Farhamzah', 'M.Farm.'));
    }
}
