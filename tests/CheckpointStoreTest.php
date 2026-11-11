<?php

namespace ONIXParser\Tests;

use ONIXParser\ChunkOnixParser;
use ONIXParser\Exception\CheckpointException;
use ONIXParser\Logger;
use ONIXParser\Resume\CheckpointStore;
use ONIXParser\Resume\FileCheckpointStore;
use ONIXParser\Tests\Support\OnixFile;
use PHPUnit\Framework\TestCase;

class CheckpointStoreTest extends TestCase
{
    private $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $f) {
            @unlink($f);
        }
    }

    private function onix(int $n): string
    {
        return $this->files[] = OnixFile::create($n);
    }

    private function parser(string $file): ChunkOnixParser
    {
        $cp = $file . '.cp';
        $this->files[] = $cp;
        $p = new ChunkOnixParser($file, null, new Logger());
        $p->setCheckpointFile($cp);
        return $p;
    }

    /** Stops after $n records, leaving a checkpoint. */
    private function stopAfter(ChunkOnixParser $p, int $n): void
    {
        $seen = 0;
        $p->parseWithCheckpoints(function () use (&$seen, $n) {
            $seen++;
            return $seen < $n ? true : false; // false = old contract: stop after this record
        }, 1000);
    }

    public function testFileStoreRoundTripAndNoTempLeftover(): void
    {
        $path = sys_get_temp_dir() . '/cpstore' . uniqid() . '.json';
        $this->files[] = $path;
        $store = new FileCheckpointStore($path);
        $this->assertNull($store->load());
        $store->save(['position' => 123, 'count' => 4, 'x' => 'y']);
        $this->assertSame(['position' => 123, 'count' => 4, 'x' => 'y'], $store->load());
        $this->assertSame([], glob(dirname($path) . '/.' . basename($path) . '.*.tmp'));
        $store->save(['position' => 999, 'count' => 9]);
        $this->assertSame(999, $store->load()['position']);
        $store->clear();
        $this->assertNull($store->load());
    }

    public function testTruncatedCheckpointRaisesAndDoesNotRestartAtZero(): void
    {
        $file = $this->onix(20);
        $p = $this->parser($file);
        $this->stopAfter($p, 5);
        $cp = $file . '.cp';
        $good = file_get_contents($cp);
        $this->assertNotEmpty($good);
        // deliberately cut the checkpoint in half
        file_put_contents($cp, substr($good, 0, (int)(strlen($good) / 2)));

        $count = 0;
        try {
            $this->parser($file)->parseWithCheckpoints(function () use (&$count) {
                $count++;
                return true;
            });
            $this->fail('A truncated checkpoint must raise an error');
        } catch (CheckpointException $e) {
            $this->assertStringContainsString('corrupted', $e->getMessage());
        }
        $this->assertSame(0, $count, 'no record may be parsed after a damaged checkpoint');
        $this->assertFileExists($cp, 'the damaged checkpoint is kept for inspection');
    }

    public function testEmptyCheckpointFileRaises(): void
    {
        $file = $this->onix(3);
        $p = $this->parser($file);
        file_put_contents($file . '.cp', '');
        $this->expectException(CheckpointException::class);
        $p->getCheckpointInfo();
    }

    public function testCheckpointWithoutPositionRaises(): void
    {
        $file = $this->onix(3);
        $p = $this->parser($file);
        file_put_contents($file . '.cp', '{"count": 3}');
        $this->expectException(CheckpointException::class);
        $p->getCheckpointInfo();
    }

    public function testCheckpointHasNoFullFileHash(): void
    {
        $file = $this->onix(10);
        $p = $this->parser($file);
        $this->stopAfter($p, 3);
        $info = $p->getCheckpointInfo();
        $this->assertArrayNotHasKey('file_hash', $info);
        $this->assertSame(filesize($file), $info['file_size']);
        $this->assertSame(filemtime($file), $info['file_mtime']);
        $this->assertSame(3, $info['count']);
        $this->assertGreaterThan(0, $info['position']);
    }

    public function testChangedFileSizeRaises(): void
    {
        $file = $this->onix(10);
        $p = $this->parser($file);
        $this->stopAfter($p, 3);
        file_put_contents($file, "<!-- more -->\n", FILE_APPEND);
        clearstatcache();
        $this->expectException(CheckpointException::class);
        $this->parser($file)->getCheckpointInfo();
    }

    public function testChangedMtimeRaisesWithoutCallerHash(): void
    {
        $file = $this->onix(10);
        $p = $this->parser($file);
        $this->stopAfter($p, 3);
        touch($file, filemtime($file) + 100);
        clearstatcache();
        $this->expectException(CheckpointException::class);
        $this->parser($file)->getCheckpointInfo();
    }

    public function testCallerHashOverridesMtime(): void
    {
        $file = $this->onix(10);
        $p = $this->parser($file);
        $p->setFileHash('abc123');
        $this->stopAfter($p, 3);
        touch($file, filemtime($file) + 100); // same content copied again: new mtime
        clearstatcache();

        $same = $this->parser($file);
        $same->setFileHash('abc123');
        $this->assertSame(3, $same->getCheckpointInfo()['count']);

        $other = $this->parser($file);
        $other->setFileHash('different');
        $this->expectException(CheckpointException::class);
        $other->getCheckpointInfo();
    }

    public function testCustomStoreReceivesTheCheckpoints(): void
    {
        $file = $this->onix(10);
        $store = new class implements CheckpointStore {
            public $saved = [];
            public $data = null;
            public function load(): ?array { return $this->data; }
            public function save(array $c): void { $this->saved[] = $c; $this->data = $c; }
            public function clear(): void { $this->data = null; }
        };
        $p = new ChunkOnixParser($file, null, new Logger());
        $p->setCheckpointStore($store);
        $n = 0;
        $p->parseWithCheckpoints(function () use (&$n) {
            $n++;
            return $n < 4 ? true : false;
        }, 2);
        // interval 2 -> checkpoint at record 2, then the stop checkpoint after record 4
        $this->assertSame([2, 4], array_column($store->saved, 'count'));
        $this->assertSame(4, $store->load()['count']);

        // a second parser using the same store resumes from the store
        $p2 = new ChunkOnixParser($file, null, new Logger());
        $p2->setCheckpointStore($store);
        $refs = [];
        $p2->parseWithCheckpoints(function ($product) use (&$refs) {
            $refs[] = $product->getRecordReference();
            return true;
        });
        $this->assertSame(['rec.5', 'rec.6', 'rec.7', 'rec.8', 'rec.9', 'rec.10'], $refs);
        $this->assertNull($store->load(), 'cleared at the end of the file');
    }
}
