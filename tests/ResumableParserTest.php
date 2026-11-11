<?php

namespace ONIXParser\Tests;

use ONIXParser\Tests\Support\OnixFile;
use ONIXParser\Tests\Support\ProductFactory;
use ONIXParser\Tests\Support\ResumeProof;
use PHPUnit\Framework\TestCase;

/**
 * Resume tests for ChunkOnixParser (the parser the plugin really uses).
 * The old version of this file tested Resume/* classes and a ResumableOnixParser that no longer exists.
 *
 * Every session runs in its own PHP process (tests/tools/resume-runner.php), so "kill" is a real SIGKILL.
 */
class ResumableParserTest extends TestCase
{
    private $dir;
    private $file;
    /** @var ResumeProof */
    private $proof;

    protected function setUp(): void
    {
        if (!function_exists('posix_kill') || !function_exists('proc_open')) {
            $this->markTestSkipped('posix and proc_open are needed');
        }
        $this->dir = sys_get_temp_dir() . '/resume' . uniqid();
        $this->proof = new ResumeProof($this->dir);
        // records with prices, taxes, blocks, accents, several sizes
        $this->file = OnixFile::create(900, function ($i) {
            $extra = '';
            if ($i % 3 === 0) {
                $extra .= '<CollateralDetail><TextContent><TextType>03</TextType><ContentAudience>00</ContentAudience><Text textformat="05">Résumé ' . $i . ' ' . str_repeat('é', $i % 50) . '</Text></TextContent></CollateralDetail>';
            }
            $extra .= '<PublishingDetail><Imprint><ImprintIdentifier><ImprintIDType>06</ImprintIDType><IDValue>3052090010015</IDValue></ImprintIdentifier><ImprintName>X</ImprintName></Imprint></PublishingDetail>';
            return $extra;
        });
        // add a price to every record through a second file pass is not needed: prices come from OnixFile supply
    }

    protected function tearDown(): void
    {
        @unlink($this->file);
        foreach (glob($this->dir . '/*') ?: [] as $f) {
            @unlink($f);
        }
        @rmdir($this->dir);
    }

    private function clean(): array
    {
        $this->proof->reset('clean');
        $r = $this->proof->session($this->file, 'clean', 'full');
        $this->assertSame(0, $r['exit'], $r['raw']);
        $this->assertTrue($r['json']['completed']);
        $this->assertSame(900, $r['json']['count']);
        return ['sha' => $this->proof->sha('clean'), 'lines' => $this->proof->lines('clean')];
    }

    public function testStopAfterThreeTimesGivesTheSameOutput(): void
    {
        $clean = $this->clean();
        $this->assertSame(900, $clean['lines']);
        $r = $this->proof->resumeRun($this->file, 'after', [['after', 222], ['after', 222], ['after', 222]], 100);
        $this->assertSame(900, $r['lines']);
        $this->assertSame($clean['sha'], $r['sha']);
    }

    public function testStopBeforeThreeTimesGivesTheSameOutput(): void
    {
        $clean = $this->clean();
        $r = $this->proof->resumeRun($this->file, 'before', [['before', 250], ['before', 250], ['before', 250]], 100);
        $this->assertSame(900, $r['lines']);
        $this->assertSame($clean['sha'], $r['sha']);
    }

    public function testSigkillThreeTimesGivesTheSameOutput(): void
    {
        $clean = $this->clean();
        // checkpoint every 100: a kill after 230 records loses the 30 since the last checkpoint, they are redone
        $r = $this->proof->resumeRun($this->file, 'kill', [['kill', 230], ['kill', 230], ['kill', 230]], 100);
        foreach (array_slice($r['sessions'], 0, 3) as $s) {
            $this->assertNotSame(0, $s['exit'], 'the session must really die');
        }
        $this->assertSame(900, $r['lines']);
        $this->assertSame($clean['sha'], $r['sha']);
    }

    public function testMixedStopsGiveTheSameOutput(): void
    {
        $clean = $this->clean();
        $r = $this->proof->resumeRun($this->file, 'mixed', [['after', 111], ['kill', 150], ['before', 99], ['kill', 80]], 50);
        $this->assertSame(900, $r['lines']);
        $this->assertSame($clean['sha'], $r['sha']);
    }

    public function testOutputIsInFileOrderWithNoRepeat(): void
    {
        $this->proof->resumeRun($this->file, 'order', [['after', 300], ['kill', 100]], 50);
        $refs = [];
        foreach (file($this->proof->outPath('order')) as $line) {
            $refs[] = json_decode($line, true)['ref'];
        }
        $this->assertSame(array_map(function ($i) {
            return "rec.$i";
        }, range(1, 900)), $refs);
    }

    public function testFinishedFileLeavesNoCheckpoint(): void
    {
        $this->clean();
        $this->assertFileDoesNotExist($this->proof->cpPath('clean'));
    }

    public function testRealFileKillThreeTimes(): void
    {
        $real = getenv('ONIX_REAL_FILE');
        if (!$real || !is_file($real)) {
            $this->markTestSkipped('ONIX_REAL_FILE not set');
        }
        $this->proof->reset('realclean');
        $c = $this->proof->session($real, 'realclean', 'full');
        $this->assertTrue($c['json']['completed'], $c['raw']);
        $total = $c['json']['count'];
        $this->assertGreaterThan(0, $total);
        $r = $this->proof->resumeRun($real, 'realresume', [['after', 7777], ['kill', 7777], ['before', 7777]], 1000);
        $this->assertSame($total, $r['lines']);
        $this->assertSame($this->proof->sha('realclean'), $r['sha']);
        fwrite(STDERR, sprintf("\nreal file: %d records, sha256 %s, clean %.1fs %.1fMB\n", $total, $r['sha'], $c['json']['seconds'], $c['json']['peak_mb']));
    }
}
