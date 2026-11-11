<?php

namespace ONIXParser\Resume;

use ONIXParser\Exception\CheckpointException;

/**
 * Default checkpoint store: one JSON file, written atomically.
 *
 * Write = temp file in the same directory, fwrite, fflush, fsync, rename over the target.
 * A crash leaves either the old file or the new one, never half of one.
 * Read = a damaged file raises CheckpointException; it is never read as "no checkpoint".
 */
class FileCheckpointStore implements CheckpointStore
{
    /** @var string */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function load(): ?array
    {
        if (!file_exists($this->path)) {
            return null;
        }

        $json = @file_get_contents($this->path);
        if ($json === false) {
            throw CheckpointException::loadFailed($this->path, 'file exists but cannot be read');
        }
        if (trim($json) === '') {
            throw CheckpointException::corrupted($this->path);
        }

        $data = json_decode($json, true);
        if (!is_array($data)
            || !isset($data['position'], $data['count'])
            || !is_int($data['position']) || !is_int($data['count'])
            || $data['position'] < 0 || $data['count'] < 0
        ) {
            throw CheckpointException::corrupted($this->path);
        }

        return $data;
    }

    public function save(array $checkpoint): void
    {
        $json = json_encode($checkpoint, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            throw CheckpointException::createFailed('cannot encode checkpoint: ' . json_last_error_msg());
        }

        $dir = dirname($this->path);
        $temp = $dir . DIRECTORY_SEPARATOR . '.' . basename($this->path) . '.' . getmypid() . '.tmp';

        $handle = @fopen($temp, 'wb');
        if ($handle === false) {
            throw CheckpointException::createFailed("cannot open temp file $temp");
        }

        $ok = fwrite($handle, $json) === strlen($json) && fflush($handle);
        if ($ok) {
            // fsync() exists from PHP 8.1; before that fflush() is the best we have
            $ok = function_exists('fsync') ? fsync($handle) : true;
        }
        fclose($handle);

        if (!$ok || !@rename($temp, $this->path)) {
            @unlink($temp);
            throw CheckpointException::createFailed("cannot write {$this->path}");
        }
    }

    public function clear(): void
    {
        if (file_exists($this->path)) {
            @unlink($this->path);
        }
    }
}
