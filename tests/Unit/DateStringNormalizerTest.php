<?php

namespace Tests\Unit;

use App\Support\DateStringNormalizer;
use Tests\TestCase;

class DateStringNormalizerTest extends TestCase
{
    public function test_normalizes_supported_date_strings_to_iso(): void
    {
        $this->assertSame('2026-07-05', DateStringNormalizer::toIso('05/07/2026'));
        $this->assertSame('2026-07-05', DateStringNormalizer::toIso('5/7/2026'));
        $this->assertSame('2026-07-05', DateStringNormalizer::toIso('2026-7-5'));
        $this->assertNull(DateStringNormalizer::toIso(''));
        $this->assertNull(DateStringNormalizer::toIso('không rõ'));
    }

    public function test_normalizes_to_display_date(): void
    {
        $this->assertSame('05/07/2026', DateStringNormalizer::toDisplay('2026-07-05'));
        $this->assertSame('không rõ', DateStringNormalizer::toDisplay('không rõ'));
    }
}
