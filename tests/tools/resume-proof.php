<?php
/**
 * Resume proof on a real file: clean run vs stopped/killed/resumed runs, SHA-256 of the output.
 *   php tests/tools/resume-proof.php FILE.xml [WORKDIR]
 * Exit 0 only if every variant is byte-identical to the clean run.
 */
require __DIR__ . '/../../vendor/autoload.php';

use ONIXParser\Tests\Support\ResumeProof;

$file = $argv[1] ?? null;
if (!$file || !is_file($file)) {
    fwrite(STDERR, "Usage: php resume-proof.php FILE.xml [WORKDIR]\n");
    exit(2);
}
$work = $argv[2] ?? sys_get_temp_dir() . '/resume-proof-' . getmypid();
$proof = new ResumeProof($work);

$proof->reset('clean');
$c = $proof->session($file, 'clean', 'full');
$total = $c['json']['count'] ?? 0;
printf("CLEAN       %d records  %.1fs  peak %.1f MB  sha256 %s\n", $total, $c['json']['seconds'] ?? 0, $c['json']['peak_mb'] ?? 0, $proof->sha('clean'));

$variants = [
    'stop-after'  => [['after', 7777], ['after', 7777], ['after', 7777]],
    'stop-before' => [['before', 7777], ['before', 7777], ['before', 7777]],
    'sigkill'     => [['kill', 7777], ['kill', 7777], ['kill', 7777]],
    'mixed'       => [['after', 7777], ['kill', 7777], ['before', 7777]],
];
$ok = true;
foreach ($variants as $name => $modes) {
    $r = $proof->resumeRun($file, $name, $modes, 1000);
    $same = $r['sha'] === $proof->sha('clean') && $r['lines'] === $total;
    $ok = $ok && $same;
    $desc = [];
    foreach ($r['sessions'] as $s) {
        $desc[] = sprintf('%s%s exit=%d%s', $s['mode'], $s['n'] ? ':' . $s['n'] : '', $s['exit'], isset($s['json']['count']) ? ' count=' . $s['json']['count'] : '');
    }
    printf("%-11s %d records  %s  sha256 %s\n            sessions: %s\n", strtoupper($name), $r['lines'], $same ? 'IDENTICAL' : 'DIFFERENT', $r['sha'], implode(' | ', $desc));
}
echo $ok ? "PASS: all variants identical to the clean run\n" : "FAIL\n";
exit($ok ? 0 : 1);
