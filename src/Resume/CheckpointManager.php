<?php

namespace ONIXParser\Resume;

use ONIXParser\Exception\CheckpointException;
use ONIXParser\Exception\ResumeException;
use ONIXParser\Logger;

/**
 * Manages checkpoint creation, validation, and cleanup
 * Handles atomic checkpoint saves and resume point validation
 */
class CheckpointManager
{
    /** @var string Directory for storing checkpoint files */
    private $checkpointDir;
    
    /** @var Logger */
    private $logger;
    
    /** @var int Maximum age of checkpoints in seconds */
    private $maxCheckpointAge;
    
    /** @var int Maximum number of checkpoints to keep */
    private $maxCheckpoints;
    
    /** @var bool Whether to compress checkpoint data */
    private $compressCheckpoints;
    
    public function __construct(
        string $checkpointDir = null,
        Logger $logger = null,
        int $maxCheckpointAge = 86400, // 24 hours
        int $maxCheckpoints = 10,
        bool $compressCheckpoints = false
    ) {
        $this->checkpointDir = $checkpointDir ?: sys_get_temp_dir() . '/onix_checkpoints';
        $this->logger = $logger ?: new Logger();
        $this->maxCheckpointAge = $maxCheckpointAge;
        $this->maxCheckpoints = $maxCheckpoints;
        $this->compressCheckpoints = $compressCheckpoints;
        
        $this->ensureCheckpointDirectory();
    }
    
    /**
     * Create checkpoint directory if it doesn't exist
     */
    private function ensureCheckpointDirectory(): void
    {
        if (!is_dir($this->checkpointDir)) {
            if (!mkdir($this->checkpointDir, 0755, true)) {
                throw CheckpointException::createFailed("Cannot create checkpoint directory: {$this->checkpointDir}");
            }
        }
        
        if (!is_writable($this->checkpointDir)) {
            throw CheckpointException::createFailed("Checkpoint directory is not writable: {$this->checkpointDir}");
        }
    }
    
    /**
     * Save checkpoint to file with atomic write
     */
    public function saveCheckpoint(ResumePoint $resumePoint, string $checkpointId = null): string
    {
        $checkpointId = $checkpointId ?: $this->generateCheckpointId($resumePoint);
        $checkpointFile = $this->getCheckpointFilePath($checkpointId);
        $tempFile = $checkpointFile . '.tmp';
        
        try {
            // Prepare checkpoint data
            $checkpointData = [
                'version' => '1.0',
                'created_at' => time(),
                'checkpoint_id' => $checkpointId,
                'resume_point' => $resumePoint->toArray(),
            ];
            
            // Serialize data
            $jsonData = json_encode($checkpointData, JSON_PRETTY_PRINT);
            if ($jsonData === false) {
                throw CheckpointException::createFailed("JSON encoding failed: " . json_last_error_msg());
            }
            
            // Compress if enabled
            if ($this->compressCheckpoints) {
                $jsonData = gzencode($jsonData);
                if ($jsonData === false) {
                    throw CheckpointException::createFailed("Compression failed");
                }
            }
            
            // Atomic write using temporary file
            if (file_put_contents($tempFile, $jsonData, LOCK_EX) === false) {
                throw CheckpointException::createFailed("Failed to write temporary checkpoint file");
            }
            
            // Atomic rename
            if (!rename($tempFile, $checkpointFile)) {
                throw CheckpointException::createFailed("Failed to rename temporary checkpoint file");
            }
            
            $this->logger->info("Checkpoint saved: {$checkpointFile}");
            
            // Cleanup old checkpoints
            $this->cleanupOldCheckpoints();
            
            return $checkpointFile;
            
        } catch (\Exception $e) {
            // Clean up temporary file if it exists
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
            throw $e;
        }
    }
    
    /**
     * Load checkpoint from file
     */
    public function loadCheckpoint(string $checkpointFile): ResumePoint
    {
        if (!file_exists($checkpointFile)) {
            throw CheckpointException::loadFailed($checkpointFile, "File does not exist");
        }
        
        if (!is_readable($checkpointFile)) {
            throw CheckpointException::loadFailed($checkpointFile, "File is not readable");
        }
        
        try {
            $data = file_get_contents($checkpointFile);
            if ($data === false) {
                throw CheckpointException::loadFailed($checkpointFile, "Failed to read file");
            }
            
            // Decompress if needed
            if ($this->compressCheckpoints) {
                $decompressed = gzdecode($data);
                if ($decompressed === false) {
                    throw CheckpointException::loadFailed($checkpointFile, "Decompression failed");
                }
                $data = $decompressed;
            }
            
            $checkpointData = json_decode($data, true);
            if ($checkpointData === null) {
                throw CheckpointException::loadFailed($checkpointFile, "JSON decoding failed: " . json_last_error_msg());
            }
            
            // Validate checkpoint structure
            $this->validateCheckpointData($checkpointData);
            
            // Create resume point
            $resumePoint = ResumePoint::fromArray($checkpointData['resume_point']);
            
            $this->logger->info("Checkpoint loaded: {$checkpointFile}");
            
            return $resumePoint;
            
        } catch (\Exception $e) {
            throw CheckpointException::loadFailed($checkpointFile, $e->getMessage());
        }
    }
    
    /**
     * Validate checkpoint data structure
     */
    private function validateCheckpointData(array $data): void
    {
        $requiredFields = ['version', 'created_at', 'checkpoint_id', 'resume_point'];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw CheckpointException::corrupted("Missing required field: {$field}");
            }
        }
        
        if (!is_array($data['resume_point'])) {
            throw CheckpointException::corrupted("Invalid resume_point data");
        }
        
        // Check version compatibility
        if ($data['version'] !== '1.0') {
            throw CheckpointException::loadFailed('checkpoint', "Unsupported checkpoint version: {$data['version']}");
        }
    }
    
    /**
     * Check if checkpoint exists
     */
    public function hasCheckpoint(string $checkpointId): bool
    {
        $checkpointFile = $this->getCheckpointFilePath($checkpointId);
        return file_exists($checkpointFile);
    }
    
    /**
     * Delete checkpoint file
     */
    public function deleteCheckpoint(string $checkpointId): bool
    {
        $checkpointFile = $this->getCheckpointFilePath($checkpointId);
        
        if (!file_exists($checkpointFile)) {
            return true; // Already deleted
        }
        
        if (unlink($checkpointFile)) {
            $this->logger->info("Checkpoint deleted: {$checkpointFile}");
            return true;
        }
        
        return false;
    }
    
    /**
     * List all available checkpoints
     */
    public function listCheckpoints(): array
    {
        $checkpoints = [];
        $pattern = $this->checkpointDir . '/*.checkpoint';
        
        foreach (glob($pattern) as $file) {
            $checkpointId = basename($file, '.checkpoint');
            $checkpoints[] = [
                'id' => $checkpointId,
                'file' => $file,
                'created_at' => filemtime($file),
                'size' => filesize($file),
            ];
        }
        
        // Sort by creation time (newest first)
        usort($checkpoints, function($a, $b) {
            return $b['created_at'] - $a['created_at'];
        });
        
        return $checkpoints;
    }
    
    /**
     * Clean up old checkpoints
     */
    public function cleanupOldCheckpoints(): int
    {
        $checkpoints = $this->listCheckpoints();
        $deleted = 0;
        
        foreach ($checkpoints as $index => $checkpoint) {
            $shouldDelete = false;
            
            // Delete if too old
            if (time() - $checkpoint['created_at'] > $this->maxCheckpointAge) {
                $shouldDelete = true;
            }
            
            // Delete if exceeds maximum count (keep newest)
            if ($index >= $this->maxCheckpoints) {
                $shouldDelete = true;
            }
            
            if ($shouldDelete) {
                if (unlink($checkpoint['file'])) {
                    $deleted++;
                    $this->logger->debug("Deleted old checkpoint: {$checkpoint['file']}");
                }
            }
        }
        
        if ($deleted > 0) {
            $this->logger->info("Cleaned up {$deleted} old checkpoints");
        }
        
        return $deleted;
    }
    
    /**
     * Generate unique checkpoint ID
     */
    private function generateCheckpointId(ResumePoint $resumePoint): string
    {
        $components = [
            basename($resumePoint->getFilePath()),
            $resumePoint->getBytePosition(),
            $resumePoint->getParserState()->getProcessedProductCount(),
            time(),
        ];
        
        return md5(implode('|', $components));
    }
    
    /**
     * Get full path to checkpoint file
     */
    private function getCheckpointFilePath(string $checkpointId): string
    {
        return $this->checkpointDir . '/' . $checkpointId . '.checkpoint';
    }
    
    /**
     * Get checkpoint file path by file path and position
     */
    public function getCheckpointFileByPath(string $filePath): ?string
    {
        $checkpoints = $this->listCheckpoints();
        
        foreach ($checkpoints as $checkpoint) {
            try {
                $resumePoint = $this->loadCheckpoint($checkpoint['file']);
                if ($resumePoint->getFilePath() === $filePath) {
                    return $checkpoint['file'];
                }
            } catch (\Exception $e) {
                // Skip corrupted checkpoints
                continue;
            }
        }
        
        return null;
    }
    
    /**
     * Validate checkpoint against current file
     */
    public function validateCheckpoint(ResumePoint $resumePoint): bool
    {
        try {
            $resumePoint->validate();
            $resumePoint->validateXmlContext();
            return true;
        } catch (\Exception $e) {
            $this->logger->warning("Checkpoint validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get checkpoint statistics
     */
    public function getStats(): array
    {
        $checkpoints = $this->listCheckpoints();
        $totalSize = array_sum(array_column($checkpoints, 'size'));
        
        return [
            'checkpoint_count' => count($checkpoints),
            'total_size' => $totalSize,
            'checkpoint_dir' => $this->checkpointDir,
            'max_age' => $this->maxCheckpointAge,
            'max_count' => $this->maxCheckpoints,
            'compression_enabled' => $this->compressCheckpoints,
        ];
    }
    
    // Getters and setters
    public function getCheckpointDir(): string { return $this->checkpointDir; }
    public function setMaxCheckpointAge(int $age): void { $this->maxCheckpointAge = $age; }
    public function setMaxCheckpoints(int $count): void { $this->maxCheckpoints = $count; }
    public function setCompression(bool $enabled): void { $this->compressCheckpoints = $enabled; }
}