<?php

namespace ONIXParser\Exception;

use Exception;

/**
 * Exception thrown when checkpoint operations fail
 */
class CheckpointException extends Exception
{
    /**
     * Create exception for checkpoint creation failure
     */
    public static function createFailed(string $reason): self
    {
        return new self("Failed to create checkpoint: {$reason}");
    }
    
    /**
     * Create exception for checkpoint loading failure
     */
    public static function loadFailed(string $checkpointFile, string $reason): self
    {
        return new self("Failed to load checkpoint from '{$checkpointFile}': {$reason}");
    }
    
    /**
     * Create exception for checkpoint validation failure
     */
    public static function validationFailed(string $reason): self
    {
        return new self("Checkpoint validation failed: {$reason}");
    }
    
    /**
     * Create exception for checkpoint corruption
     */
    public static function corrupted(string $checkpointFile): self
    {
        return new self("Checkpoint file '{$checkpointFile}' is corrupted or invalid");
    }
    
    /**
     * Create exception for file integrity check failure
     */
    public static function fileIntegrityFailed(string $originalFile): self
    {
        return new self("File '{$originalFile}' has been modified since checkpoint was created");
    }
}