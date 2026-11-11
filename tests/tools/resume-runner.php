<?php
/**
 * One parsing session for the resume tests. Writes one line per record to OUT.
 *
 *   php resume-runner.php FILE OUT CHECKPOINT MODE [N] [INTERVAL]
 *
 * MODE  full         run to the end of the file
 *       after N      handle N records, return STOP_AFTER on the Nth
 *       before N     handle N records, then return STOP_BEFORE on the next one (not handled)
 *       kill N       handle N records, then SIGKILL itself (no clean exit, no final checkpoint)
 *
 * OUT and the checkpoint stay consistent: every checkpoint also stores the size of OUT at that moment
 * ('out_size'). On start the runner cuts OUT back to that size, so records written after the last
 * checkpoint (lost by a kill) are written again and nothing is written twice.
 *
 * Prints one JSON line at the end: processed, count, completed, peak memory, seconds.
 */
require __DIR__ . '/../../vendor/autoload.php';

use ONIXParser\ChunkOnixParser;
use ONIXParser\Logger;
use ONIXParser\Resume\FileCheckpointStore;
use ONIXParser\Tests\Support\ProductDump;

[$script, $file, $out, $cpPath, $mode] = array_pad($argv, 5, null);
$n = (int)($argv[5] ?? 0);
$interval = (int)($argv[6] ?? 1000);

final class OutAwareStore extends FileCheckpointStore
{
    /** @var resource */
    public $outHandle;

    public function save(array $checkpoint): void
    {
        fflush($this->outHandle);
        clearstatcache();
        $checkpoint['out_size'] = ftell($this->outHandle);
        parent::save($checkpoint);
    }
}

$store = new OutAwareStore($cpPath);
$existing = $store->load();
$outHandle = fopen($out, file_exists($out) ? 'r+b' : 'w+b');
ftruncate($outHandle, (int)($existing['out_size'] ?? 0));
fseek($outHandle, 0, SEEK_END);
$store->outHandle = $outHandle;

$parser = new ChunkOnixParser($file, null, new Logger(Logger::ERROR));
$parser->setCheckpointStore($store);
$t = microtime(true);
$session = 0;

$result = $parser->parseWithCheckpoints(function ($product) use (&$session, $mode, $n, $outHandle) {
    if ($mode === 'before' && $session >= $n) {
        return ChunkOnixParser::STOP_BEFORE;
    }
    fwrite($outHandle, ProductDump::line($product) . "\n");
    $session++;
    if ($mode === 'after' && $session >= $n) {
        return ChunkOnixParser::STOP_AFTER;
    }
    if ($mode === 'kill' && $session >= $n) {
        fflush($outHandle);
        posix_kill(getmypid(), SIGKILL);
        sleep(5);
    }
    return true;
}, $interval);

fflush($outHandle);
fclose($outHandle);
echo json_encode($result + ['peak_mb' => round(memory_get_peak_usage(true) / 1048576, 1), 'seconds' => round(microtime(true) - $t, 1)]) . "\n";
