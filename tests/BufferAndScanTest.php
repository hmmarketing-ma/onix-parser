<?php

namespace ONIXParser\Tests;

use ONIXParser\ChunkOnixParser;
use ONIXParser\Exception\RecordTooLargeException;
use ONIXParser\Exception\TruncatedFileException;
use ONIXParser\Logger;
use ONIXParser\Tests\Support\OnixFile;
use PHPUnit\Framework\TestCase;

class BufferAndScanTest extends TestCase
{
    private $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $f) {
            @unlink($f);
        }
    }

    private function track(string $f): string
    {
        $this->files[] = $f;
        $this->files[] = $f . '.chunk_checkpoint';
        return $f;
    }

    private function refs(string $file, int $chunk = 0, ?int $cap = null): array
    {
        $p = new ChunkOnixParser($file, null, new Logger());
        if ($chunk) {
            $p->setChunkSize($chunk);
        }
        if ($cap !== null) {
            $p->setMaxRecordSize($cap);
        }
        $refs = [];
        $p->parseWithCheckpoints(function ($product) use (&$refs) {
            $refs[] = $product->getRecordReference();
            return true;
        }, 100000);
        return $refs;
    }

    public function testEveryRecordIsFoundWhateverTheChunkSize(): void
    {
        $file = $this->track(OnixFile::create(60));
        $expected = array_map(function ($i) {
            return "rec.$i";
        }, range(1, 60));
        foreach ([100, 333, 1024, 4097, 65536, 512 * 1024] as $chunk) {
            $this->assertSame($expected, $this->refs($file, $chunk), "chunk size $chunk");
        }
    }

    public function testCrLfFileAndTagFollowedByCarriageReturn(): void
    {
        $file = $this->track(OnixFile::create(25, null, "\r\n"));
        $this->assertCount(25, $this->refs($file, 777));
        // '<Product' followed directly by CR
        $path = $this->track(tempnam(sys_get_temp_dir(), 'onixcr'));
        file_put_contents($path, '<?xml version="1.0"?><ONIXMessage xmlns="' . Support\ProductFactory::NS . '">'
            . "<Product\r\n><RecordReference>cr.1</RecordReference><NotificationType>03</NotificationType></Product>\r\n"
            . "<Product\r\n><RecordReference>cr.2</RecordReference><NotificationType>03</NotificationType></Product></ONIXMessage>");
        $this->assertSame(['cr.1', 'cr.2'], $this->refs($path, 50));
    }

    public function testMultiByteCharactersSplitByChunkBordersAreRejoined(): void
    {
        $file = $this->track(OnixFile::create(40)); // titles contain 'é' (2 bytes)
        $titles = [];
        $p = new ChunkOnixParser($file, null, new Logger());
        $p->setChunkSize(131); // odd size: many borders fall inside an é
        $p->parseWithCheckpoints(function ($product) use (&$titles) {
            $titles[] = $product->getTitle() ? $product->getTitle()->getText() : null;
            return true;
        }, 100000);
        $this->assertSame('Titre 1 é', $titles[0]);
        $this->assertSame('Titre 40 é', $titles[39]);
    }

    public function testProductSupplyClosingTagDoesNotEndARecord(): void
    {
        $file = $this->track(OnixFile::create(10));
        $this->assertCount(10, $this->refs($file, 64));
    }

    public function testRecordBiggerThanTheBufferIsNeverDropped(): void
    {
        // record #3 carries a 3 MB text; chunk size is 8 KB, so it spans ~380 chunks
        $big = str_repeat('Lorem ipsum dolor sit amet. ', 110000); // ~3 MB
        $file = $this->track(OnixFile::create(5, function ($i) use ($big) {
            return $i === 3 ? '<CollateralDetail><TextContent><TextType>03</TextType><ContentAudience>00</ContentAudience><Text>' . $big . '</Text></TextContent></CollateralDetail>' : '';
        }));
        $lengths = [];
        $p = new ChunkOnixParser($file, null, new Logger());
        $p->setChunkSize(8192);
        $p->parseWithCheckpoints(function ($product) use (&$lengths) {
            $d = $product->getDescriptions();
            $lengths[$product->getRecordReference()] = $d ? strlen($d[0]->getContent()) : 0;
            return true;
        }, 100000);
        $this->assertSame(['rec.1', 'rec.2', 'rec.3', 'rec.4', 'rec.5'], array_keys($lengths));
        $this->assertSame(strlen(trim($big)), $lengths['rec.3']);
    }

    public function testRecordOverTheCapThrowsWithTheByteOffsetAndKeepsEarlierRecords(): void
    {
        $big = str_repeat('x', 400000);
        $file = $this->track(OnixFile::create(5, function ($i) use ($big) {
            return $i === 3 ? '<CollateralDetail><TextContent><TextType>03</TextType><ContentAudience>00</ContentAudience><Text>' . $big . '</Text></TextContent></CollateralDetail>' : '';
        }));
        // where does record 3 start?
        $content = file_get_contents($file);
        $offset = strpos($content, '<RecordReference>rec.3<') - strlen("<Product>\n  ");

        $seen = [];
        $p = new ChunkOnixParser($file, null, new Logger());
        $p->setChunkSize(16384);
        $p->setMaxRecordSize(100000);
        try {
            $p->parseWithCheckpoints(function ($product) use (&$seen) {
                $seen[] = $product->getRecordReference();
                return true;
            }, 100000);
            $this->fail('expected RecordTooLargeException');
        } catch (RecordTooLargeException $e) {
            $this->assertSame($offset, $e->getByteOffset());
            $this->assertSame(100000, $e->getLimit());
            $this->assertGreaterThan(100000, $e->getRecordBytes());
            $this->assertStringContainsString((string)$offset, $e->getMessage());
        }
        $this->assertSame(['rec.1', 'rec.2'], $seen);
    }

    public function testTruncatedFileThrowsInsteadOfDroppingTheLastRecord(): void
    {
        $file = $this->track(OnixFile::create(6));
        $content = file_get_contents($file);
        $cut = strpos($content, '<RecordReference>rec.6<') + 40; // inside record 6
        $start6 = strrpos(substr($content, 0, $cut), '<Product>');
        file_put_contents($file, substr($content, 0, $cut));
        $seen = [];
        $p = new ChunkOnixParser($file, null, new Logger());
        try {
            $p->parseWithCheckpoints(function ($product) use (&$seen) {
                $seen[] = $product->getRecordReference();
                return true;
            }, 100000);
            $this->fail('expected TruncatedFileException');
        } catch (TruncatedFileException $e) {
            $this->assertSame($start6, $e->getByteOffset());
        }
        $this->assertCount(5, $seen);
    }

    public function testResultsAreNotAccumulated(): void
    {
        $file = $this->track(OnixFile::create(50));
        $p = new ChunkOnixParser($file, null, new Logger());
        $result = $p->parseWithCheckpoints(function () {
            return str_repeat('x', 1000); // a normal return value, ignored
        }, 100000);
        $this->assertSame(['processed', 'count', 'position', 'stopped', 'completed'], array_keys($result));
        $this->assertSame(50, $result['processed']);
        $this->assertSame(50, $result['count']);
        $this->assertTrue($result['completed']);
        $this->assertNull($result['stopped']);
        $this->assertSame(filesize($file), $result['position']);
    }

    public function testSummaryOnStop(): void
    {
        $file = $this->track(OnixFile::create(20));
        $p = new ChunkOnixParser($file, null, new Logger());
        $r = $p->parseWithCheckpoints(function ($product, $i) {
            return $i === 6 ? ChunkOnixParser::STOP_AFTER : true;
        });
        $this->assertSame(7, $r['processed']);
        $this->assertSame('after', $r['stopped']);
        $this->assertFalse($r['completed']);
        $r2 = (new ChunkOnixParser($file, null, new Logger()))->parseWithCheckpoints(function () {
            return true;
        });
        $this->assertSame(13, $r2['processed']);
        $this->assertSame(20, $r2['count']);
        $this->assertTrue($r2['completed']);
    }

    public function testGetProductCountWithSmallChunks(): void
    {
        $file = $this->track(OnixFile::create(33));
        $p = new ChunkOnixParser($file, null, new Logger());
        $p->setChunkSize(200);
        $this->assertSame(33, $p->getProductCount());
    }

    public function testMemoryStaysFlatOnAMidSizedFile(): void
    {
        $file = $this->track(OnixFile::create(4000));
        $p = new ChunkOnixParser($file, null, new Logger());
        gc_collect_cycles();
        $before = memory_get_usage();
        $peakBefore = memory_get_peak_usage();
        $p->parseWithCheckpoints(function () {
            return array_fill(0, 100, 'x');
        }, 100000);
        $this->assertLessThan(8 * 1024 * 1024, memory_get_usage() - $before);
        $this->assertLessThan(16 * 1024 * 1024, memory_get_peak_usage() - $peakBefore);
    }
}
