<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() === 'sqlite') {
            DB::getPdo()->sqliteCreateFunction('STR_TO_DATE', function (?string $date, ?string $format) {
                $date = trim((string) $date);
                $format = trim((string) $format);
                if ($date === '' || $format === '') {
                    return null;
                }

                $phpFormat = strtr($format, [
                    '%d' => 'd',
                    '%m' => 'm',
                    '%Y' => 'Y',
                    '%y' => 'y',
                    '%H' => 'H',
                    '%i' => 'i',
                    '%s' => 's',
                ]);

                $parsed = \DateTime::createFromFormat($phpFormat, $date);

                return $parsed ? $parsed->format('Y-m-d H:i:s') : null;
            }, 2);
        }
    }
}
