<?php
/**
 * Field coverage over a real ONIX file.
 *   php tests/tools/field-coverage.php /path/to/file.xml [limit]
 * Exit code 1 if any field differs from the raw XML count.
 */
require __DIR__ . '/../../vendor/autoload.php';

use ONIXParser\Tests\Support\FieldCoverage;

$file = $argv[1] ?? null;
if (!$file || !is_file($file)) {
    fwrite(STDERR, "Usage: php field-coverage.php FILE.xml [limit]\n");
    exit(2);
}
$limit = (int)($argv[2] ?? 0);
$t = microtime(true);
$c = new FieldCoverage();
$c->run($file, $limit);
echo $c->report();
printf("Time %.1fs, peak memory %.1f MB\n", microtime(true) - $t, memory_get_peak_usage(true) / 1048576);
exit($c->failures() ? 1 : 0);
