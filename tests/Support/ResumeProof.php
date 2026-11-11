<?php

namespace ONIXParser\Tests\Support;

/**
 * Drives tests/tools/resume-runner.php: a clean run, then the same file in several stopped
 * sessions, and compares the output byte for byte (SHA-256).
 */
class ResumeProof
{
    private const RUNNER = __DIR__ . '/../tools/resume-runner.php';

    /** @var string */
    private $dir;

    public function __construct(string $workDir)
    {
        $this->dir = rtrim($workDir, '/');
        if (!is_dir($this->dir)) {
            mkdir($this->dir, 0777, true);
        }
    }

    /**
     * @return array{exit:int,json:?array,raw:string}
     */
    public function session(string $file, string $tag, string $mode, int $n = 0, int $interval = 1000): array
    {
        $cmd = [PHP_BINARY, '-d', 'display_errors=stderr', self::RUNNER, $file, "{$this->dir}/$tag.out", "{$this->dir}/$tag.cp", $mode, (string)$n, (string)$interval];
        $p = proc_open($cmd, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($p);
        $lines = array_filter(explode("\n", trim($stdout)));
        $json = $lines ? json_decode(end($lines), true) : null;
        return ['exit' => $exit, 'json' => is_array($json) ? $json : null, 'raw' => $stdout . $stderr];
    }

    public function outPath(string $tag): string
    {
        return "{$this->dir}/$tag.out";
    }

    public function cpPath(string $tag): string
    {
        return "{$this->dir}/$tag.cp";
    }

    public function sha(string $tag): string
    {
        return hash_file('sha256', $this->outPath($tag));
    }

    public function lines(string $tag): int
    {
        $c = 0;
        $h = fopen($this->outPath($tag), 'rb');
        while (fgets($h) !== false) {
            $c++;
        }
        fclose($h);
        return $c;
    }

    public function reset(string $tag): void
    {
        @unlink($this->outPath($tag));
        @unlink($this->cpPath($tag));
    }

    /**
     * Run sessions until one completes. $modes is a list like [['after', 7777], ['kill', 5000], ['before', 7777]];
     * after it is used up, a final 'full' session runs to the end.
     *
     * @return array{sessions:array,sha:string,lines:int}
     */
    public function resumeRun(string $file, string $tag, array $modes, int $interval = 1000): array
    {
        $this->reset($tag);
        $sessions = [];
        foreach ($modes as [$mode, $n]) {
            $r = $this->session($file, $tag, $mode, $n, $interval);
            $sessions[] = ['mode' => $mode, 'n' => $n] + $r;
            if ($mode !== 'kill' && ($r['json']['completed'] ?? false)) {
                return ['sessions' => $sessions, 'sha' => $this->sha($tag), 'lines' => $this->lines($tag)];
            }
        }
        $r = $this->session($file, $tag, 'full', 0, $interval);
        $sessions[] = ['mode' => 'full', 'n' => 0] + $r;
        return ['sessions' => $sessions, 'sha' => $this->sha($tag), 'lines' => $this->lines($tag)];
    }
}
