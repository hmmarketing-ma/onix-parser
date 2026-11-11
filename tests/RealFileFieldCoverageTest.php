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

    /**
     * Regression: the four DILICOM test files parse with no error and the expected record counts.
     * ONIX_REAL_DIR = folder with 507693071.xml 507785858.xml 507936518.xml 508105043.xml (unzipped copies).
     */
    public function testAllFourFilesParseWithZeroErrors(): void
    {
        $dir = getenv('ONIX_REAL_DIR');
        if (!$dir || !is_dir($dir)) {
            $this->markTestSkipped('ONIX_REAL_DIR not set');
        }
        $expected = ['507693071.xml' => 27383, '507785858.xml' => 110, '507936518.xml' => 282, '508105043.xml' => 239];
        foreach ($expected as $name => $count) {
            $file = rtrim($dir, '/') . '/' . $name;
            if (!is_file($file)) {
                $this->markTestSkipped("$file missing");
            }
            $n = 0;
            $parser = new \ONIXParser\ChunkOnixParser($file, null, new \ONIXParser\Logger(\ONIXParser\Logger::ERROR));
            $parser->setCheckpointFile(sys_get_temp_dir() . '/regress-' . $name . '.cp');
            $result = $parser->parseWithCheckpoints(function () use (&$n) {
                $n++;
                return true;
            }, 100000);
            $this->assertSame($count, $n, $name);
            $this->assertSame($count, $parser->getProductCount(), "$name getProductCount");
            $this->assertTrue($result['completed']);
        }
    }
}
