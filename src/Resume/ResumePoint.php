<?php

namespace ONIXParser\Resume;

use ONIXParser\Exception\CheckpointException;
use ONIXParser\Exception\ResumeException;

/**
 * Represents a specific point in the XML file where parsing can be resumed
 * Includes byte position, validation data, and XML context information
 */
class ResumePoint
{
    /** @var int Byte position in the file */
    private $bytePosition;
    
    /** @var string File path being parsed */
    private $filePath;
    
    /** @var string MD5 hash of the file for integrity checking */
    private $fileHash;
    
    /** @var int File size when checkpoint was created */
    private $fileSize;
    
    /** @var string XML context at this position (for validation) */
    private $xmlContext;
    
    /** @var string Expected XML element name at this position */
    private $expectedElement;
    
    /** @var ParserState Parser state at this position */
    private $parserState;
    
    /** @var int Timestamp when resume point was created */
    private $createdAt;
    
    /** @var array Additional metadata */
    private $metadata;
    
    public function __construct(
        int $bytePosition,
        string $filePath,
        string $fileHash,
        int $fileSize,
        string $xmlContext,
        string $expectedElement,
        ParserState $parserState
    ) {
        $this->bytePosition = $bytePosition;
        $this->filePath = $filePath;
        $this->fileHash = $fileHash;
        $this->fileSize = $fileSize;
        $this->xmlContext = $xmlContext;
        $this->expectedElement = $expectedElement;
        $this->parserState = $parserState;
        $this->createdAt = time();
        $this->metadata = [];
    }
    
    /**
     * Validate that this resume point is still valid for the current file
     */
    public function validate(): void
    {
        if (!file_exists($this->filePath)) {
            throw ResumeException::invalidResumePoint(
                $this->bytePosition,
                "Source file '{$this->filePath}' no longer exists"
            );
        }
        
        $currentFileSize = filesize($this->filePath);
        if ($currentFileSize === false) {
            throw ResumeException::invalidResumePoint(
                $this->bytePosition,
                "Cannot determine file size for '{$this->filePath}'"
            );
        }
        
        if ($currentFileSize < $this->bytePosition) {
            throw ResumeException::invalidResumePoint(
                $this->bytePosition,
                "File has been truncated (current size: {$currentFileSize}, resume position: {$this->bytePosition})"
            );
        }
        
        // Validate file integrity using hash
        $currentHash = $this->calculateFileHash($this->filePath);
        if ($currentHash !== $this->fileHash) {
            throw CheckpointException::fileIntegrityFailed($this->filePath);
        }
        
        // Validate parser state
        if (!$this->parserState->validate()) {
            throw ResumeException::stateRestorationFailed("Parser state validation failed");
        }
    }
    
    /**
     * Validate XML context at the resume position
     */
    public function validateXmlContext(): void
    {
        $handle = fopen($this->filePath, 'r');
        if (!$handle) {
            throw ResumeException::seekFailed($this->bytePosition, "Cannot open file for reading");
        }
        
        try {
            if (fseek($handle, $this->bytePosition) !== 0) {
                throw ResumeException::seekFailed($this->bytePosition, "fseek() failed");
            }
            
            // Read a small chunk to validate XML context
            $chunk = fread($handle, 1024);
            if ($chunk === false) {
                throw ResumeException::seekFailed($this->bytePosition, "Cannot read from file");
            }
            
            // Look for the expected XML element
            if (strpos($chunk, '<' . $this->expectedElement) === false) {
                throw ResumeException::xmlContextInvalid(
                    $this->expectedElement,
                    'Element not found at resume position'
                );
            }
            
            // Additional XML well-formedness checks could be added here
            
        } finally {
            fclose($handle);
        }
    }
    
    /**
     * Calculate MD5 hash of file for integrity checking
     * Uses a consistent portion of the file (first 8KB) regardless of byte position
     */
    private function calculateFileHash(string $filePath): string
    {
        // FIXED: Use consistent hash calculation for all validation calls
        // Always hash the first 8KB of the file for consistency
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw CheckpointException::createFailed("Cannot open file for hashing");
        }
        
        try {
            $hash = hash_init('md5');
            
            // Always hash the first 8KB for consistent validation
            $hashSize = min(8192, filesize($filePath));
            $bytesRead = 0;
            
            while ($bytesRead < $hashSize) {
                $chunkSize = min(8192, $hashSize - $bytesRead);
                $chunk = fread($handle, $chunkSize);
                if ($chunk === false) {
                    break;
                }
                hash_update($hash, $chunk);
                $bytesRead += strlen($chunk);
            }
            
            return hash_final($hash);
        } finally {
            fclose($handle);
        }
    }
    
    /**
     * Serialize resume point to array for JSON storage
     */
    public function toArray(): array
    {
        return [
            'byte_position' => $this->bytePosition,
            'file_path' => $this->filePath,
            'file_hash' => $this->fileHash,
            'file_size' => $this->fileSize,
            'xml_context' => $this->xmlContext,
            'expected_element' => $this->expectedElement,
            'parser_state' => $this->parserState->toArray(),
            'created_at' => $this->createdAt,
            'metadata' => $this->metadata,
        ];
    }
    
    /**
     * Create resume point from serialized array
     */
    public static function fromArray(array $data): self
    {
        $requiredFields = [
            'byte_position', 'file_path', 'file_hash', 'file_size',
            'xml_context', 'expected_element', 'parser_state', 'created_at'
        ];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw CheckpointException::corrupted("Missing required field: {$field}");
            }
        }
        
        $parserState = ParserState::fromArray($data['parser_state']);
        
        $resumePoint = new self(
            (int)$data['byte_position'],
            (string)$data['file_path'],
            (string)$data['file_hash'],
            (int)$data['file_size'],
            (string)$data['xml_context'],
            (string)$data['expected_element'],
            $parserState
        );
        
        $resumePoint->createdAt = (int)$data['created_at'];
        $resumePoint->metadata = $data['metadata'] ?? [];
        
        return $resumePoint;
    }
    
    /**
     * Add metadata to the resume point
     */
    public function addMetadata(string $key, $value): void
    {
        $this->metadata[$key] = $value;
    }
    
    /**
     * Get age of resume point in seconds
     */
    public function getAge(): int
    {
        return time() - $this->createdAt;
    }
    
    /**
     * Check if resume point is older than specified seconds
     */
    public function isOlderThan(int $seconds): bool
    {
        return $this->getAge() > $seconds;
    }
    
    // Getters
    public function getBytePosition(): int { return $this->bytePosition; }
    public function getFilePath(): string { return $this->filePath; }
    public function getFileHash(): string { return $this->fileHash; }
    public function getFileSize(): int { return $this->fileSize; }
    public function getXmlContext(): string { return $this->xmlContext; }
    public function getExpectedElement(): string { return $this->expectedElement; }
    public function getParserState(): ParserState { return $this->parserState; }
    public function getCreatedAt(): int { return $this->createdAt; }
    public function getMetadata(): array { return $this->metadata; }
}