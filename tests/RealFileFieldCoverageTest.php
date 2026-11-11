<?php

namespace ONIXParser\Tests;

use ONIXParser\Tests\Support\FieldCoverage;
use PHPUnit\Framework\TestCase;

/**
 * Field coverage over a REAL DILICOM file (AC-12).
 * Set ONIX_REAL_FILE to an unzipped ONIX file, e.g.
 *   ONIX_REAL_FILE=/scratch/507693071.xml vendor/bin/phpunit --filter RealFileFieldCoverage
 * Without it the test is skipped (the real files are not in the repo).
 */
class RealFileFieldCoverageTest extends TestCase
{
    public function testEveryFieldMatchesRawXml(): void
    {
        $file = getenv('ONIX_REAL_FILE');
        if (!$file || !is_file($file)) {
            $this->markTestSkipped('ONIX_REAL_FILE not set');
        }
        $c = new FieldCoverage();
        $c->run($file);
        fwrite(STDERR, "\n" . $c->report());
        $this->assertGreaterThan(0, $c->getRecordCount());
        $this->assertSame([], $c->failures(), 'Fields that differ from the raw XML');
    }
}
