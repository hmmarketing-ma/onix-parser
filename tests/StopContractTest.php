<?php

namespace ONIXParser\Tests;

use ONIXParser\ChunkOnixParser;
use ONIXParser\Logger;
use ONIXParser\Tests\Support\OnixFile;
use PHPUnit\Framework\TestCase;

class StopContractTest extends TestCase
{
    private $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $f) {
            @unlink($f);
        }
    }

    private function parser(string $file): ChunkOnixParser
    {
        $this->files[] = $file . '.cp';
        $p = new ChunkOnixParser($file, null, new Logger());
        $p->setCheckpointFile($file . '.cp');
        return $p;
    }

    private function file(int $n): string
    {
        return $this->files[] = OnixFile::create($n);
    }

    /**
     * Run the file in sessions. Each session handles $perSession records, then stops with $stop.
     * With STOP_BEFORE the callback decides BEFORE it handles the record (like a time budget).
     *
     * @return string[] record references in the order they were HANDLED
     */
    private function runInSessions(string $file, int $perSession, $stop): array
    {
        $handled = [];
        for ($guard = 0; $guard < 100; $guard++) {
            $inSession = 0;
            $finished = true;
            $this->parser($file)->parseWithCheckpoints(function ($product) use (&$handled, &$inSession, &$finished, $perSession, $stop) {
                if ($stop === ChunkOnixParser::STOP_BEFORE) {
                    if ($inSession >= $perSession) {
                        $finished = false;
                        return ChunkOnixParser::STOP_BEFORE; // budget used up: record NOT handled
                    }
                    $handled[] = $product->getRecordReference();
                    $inSession++;
                    return true;
                }
                $handled[] = $product->getRecordReference();
                $inSession++;
                if ($inSession >= $perSession) {
                    $finished = false;
                    return $stop; // handled, now stop
                }
                return true;
            }, 1000);
            if ($finished) {
                break;
            }
        }
        return $handled;
    }

    private function expected(int $n): array
    {
        return array_map(function ($i) {
            return "rec.$i";
        }, range(1, $n));
    }

    public function testStopBeforeLosesNoRecordAndRepeatsNone(): void
    {
        $this->assertSame($this->expected(30), $this->runInSessions($this->file(30), 7, ChunkOnixParser::STOP_BEFORE));
    }

    public function testStopAfterLosesNoRecordAndRepeatsNone(): void
    {
        $this->assertSame($this->expected(30), $this->runInSessions($this->file(30), 7, ChunkOnixParser::STOP_AFTER));
    }

    public function testOldFalseMeansStopAfter(): void
    {
        $this->assertSame($this->expected(30), $this->runInSessions($this->file(30), 7, false));
    }

    public function testStopBeforeOnTheVeryFirstRecordLosesNothing(): void
    {
        $file = $this->file(5);
        $this->parser($file)->parseWithCheckpoints(function () {
            return ChunkOnixParser::STOP_BEFORE;
        });
        $info = $this->parser($file)->getCheckpointInfo();
        $this->assertSame(0, $info['count']);
        $refs = [];
        $this->parser($file)->parseWithCheckpoints(function ($p) use (&$refs) {
            $refs[] = $p->getRecordReference();
            return true;
        });
        $this->assertSame($this->expected(5), $refs);
    }

    public function testStopBeforeCheckpointPointsAtTheRecordStart(): void
    {
        $file = $this->file(10);
        $this->parser($file)->parseWithCheckpoints(function ($p, $index) {
            return $index === 3 ? ChunkOnixParser::STOP_BEFORE : true;
        });
        $info = $this->parser($file)->getCheckpointInfo();
        $this->assertSame(3, $info['count'], 'records 0,1,2 handled; record 3 not handled');
        $this->assertSame('<Product>', file_get_contents($file, false, null, $info['position'], 9));
        $this->assertStringContainsString('rec.4<', file_get_contents($file, false, null, $info['position'], 60));
    }

    public function testStopAfterCheckpointPointsAfterTheRecordEnd(): void
    {
        $file = $this->file(10);
        $this->parser($file)->parseWithCheckpoints(function ($p, $index) {
            return $index === 3 ? ChunkOnixParser::STOP_AFTER : true;
        });
        $info = $this->parser($file)->getCheckpointInfo();
        $this->assertSame(4, $info['count'], 'records 0..3 handled');
        $before = file_get_contents($file, false, null, $info['position'] - 10, 10);
        $this->assertSame('</Product>', $before);
    }

    public function testExceptionInCallbackRedeliversFromLastCheckpoint(): void
    {
        $file = $this->file(10);
        $seen = [];
        try {
            $this->parser($file)->parseWithCheckpoints(function ($p, $index) use (&$seen) {
                if ($index === 5) {
                    throw new \RuntimeException('boom');
                }
                $seen[] = $p->getRecordReference();
                return true;
            }, 3);
        } catch (\RuntimeException $e) {
            $this->assertSame('boom', $e->getMessage());
        }
        $this->assertSame($this->expected(5), $seen);
        $info = $this->parser($file)->getCheckpointInfo();
        $this->assertSame(3, $info['count'], 'last checkpoint was at 3 records');
        $again = [];
        $this->parser($file)->parseWithCheckpoints(function ($p) use (&$again) {
            $again[] = $p->getRecordReference();
            return true;
        });
        $this->assertSame(['rec.4', 'rec.5', 'rec.6', 'rec.7', 'rec.8', 'rec.9', 'rec.10'], $again);
    }

    public function testNormalReturnValuesNeverStop(): void
    {
        $file = $this->file(6);
        $n = 0;
        foreach ([true, null, 0, '', [], 'stop', 1, new \stdClass()] as $value) {
            $n = 0;
            $this->parser($file)->clearCheckpoint();
            $this->parser($file)->parseWithCheckpoints(function () use (&$n, $value) {
                $n++;
                return $value;
            });
            $this->assertSame(6, $n, 'return value ' . var_export($value, true) . ' must not stop');
        }
    }

    public function testStopConstantsAreDifferentAndNotFalsy(): void
    {
        $this->assertNotSame(ChunkOnixParser::STOP_AFTER, ChunkOnixParser::STOP_BEFORE);
        $this->assertTrue((bool)ChunkOnixParser::STOP_AFTER);
        $this->assertTrue((bool)ChunkOnixParser::STOP_BEFORE);
    }
}
