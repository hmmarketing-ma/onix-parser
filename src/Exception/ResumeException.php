<?php

namespace ONIXParser\Exception;

use Exception;

/**
 * Exception thrown when resume operations fail
 */
class ResumeException extends Exception
{
    /**
     * Create exception for resume point validation failure
     */
    public static function invalidResumePoint(int $bytePosition, string $reason): self
    {
        return new self("Invalid resume point at byte position {$bytePosition}: {$reason}");
    }
    
    /**
     * Create exception for file positioning failure
     */
    public static function seekFailed(int $bytePosition, string $reason): self
    {
        return new self("Failed to seek to byte position {$bytePosition}: {$reason}");
    }
    
    /**
     * Create exception for parser state restoration failure
     */
    public static function stateRestorationFailed(string $reason): self
    {
        return new self("Failed to restore parser state: {$reason}");
    }
    
    /**
     * Create exception for XML context validation failure
     */
    public static function xmlContextInvalid(string $expectedContext, string $actualContext): self
    {
        return new self("XML context mismatch at resume point. Expected: {$expectedContext}, Got: {$actualContext}");
    }
    
    /**
     * Create exception for file handle synchronization failure
     */
    public static function syncFailed(string $reason): self
    {
        return new self("Failed to synchronize file handles: {$reason}");
    }
}