<?php

namespace ONIXParser\Resume;

use ONIXParser\Exception\ResumeException;

/**
 * Tracks file positions during XML parsing
 * Synchronizes between XMLReader and file handle positions
 */
class FilePositionTracker
{
    /** @var resource File handle for position tracking */
    private $fileHandle;
    
    /** @var string Path to the file being tracked */
    private $filePath;
    
    /** @var int Current byte position in file */
    private $currentPosition;
    
    /** @var array Buffer for recent positions */
    private $positionBuffer;
    
    /** @var int Buffer size for position history */
    private $bufferSize;
    
    /** @var array Statistics for performance monitoring */
    private $stats;
    
    public function __construct(string $filePath, int $bufferSize = 1000)
    {
        $this->filePath = $filePath;
        $this->bufferSize = $bufferSize;
        $this->positionBuffer = [];
        $this->currentPosition = 0;
        $this->stats = [
            'seeks' => 0,
            'position_queries' => 0,
            'sync_operations' => 0,
        ];
        
        $this->openFile();
    }
    
    /**
     * Open file handle for position tracking
     */
    private function openFile(): void
    {
        $this->fileHandle = fopen($this->filePath, 'r');
        if (!$this->fileHandle) {
            throw ResumeException::seekFailed(0, "Cannot open file '{$this->filePath}' for position tracking");
        }
    }
    
    /**
     * Get current byte position in file
     */
    public function getCurrentPosition(): int
    {
        $this->stats['position_queries']++;
        
        if ($this->fileHandle) {
            $position = ftell($this->fileHandle);
            if ($position !== false) {
                $this->currentPosition = $position;
                return $position;
            }
        }
        
        return $this->currentPosition;
    }
    
    /**
     * Seek to specific byte position
     */
    public function seekToPosition(int $position): void
    {
        $this->stats['seeks']++;
        
        if (!$this->fileHandle) {
            throw ResumeException::seekFailed($position, "File handle not available");
        }
        
        if (fseek($this->fileHandle, $position) !== 0) {
            throw ResumeException::seekFailed($position, "fseek() failed");
        }
        
        $this->currentPosition = $position;
    }
    
    /**
     * Record a significant position (e.g., start of product element)
     */
    public function recordPosition(string $context, int $productCount = 0): void
    {
        $position = $this->getCurrentPosition();
        
        $record = [
            'position' => $position,
            'context' => $context,
            'product_count' => $productCount,
            'timestamp' => microtime(true),
        ];
        
        // Add to buffer, maintaining size limit
        $this->positionBuffer[] = $record;
        if (count($this->positionBuffer) > $this->bufferSize) {
            array_shift($this->positionBuffer);
        }
    }
    
    /**
     * Get the most recent position record
     */
    public function getLastPosition(): ?array
    {
        return !empty($this->positionBuffer) ? end($this->positionBuffer) : null;
    }
    
    /**
     * Find position record by product count
     */
    public function findPositionByProductCount(int $productCount): ?array
    {
        // Search backwards through buffer for exact match
        for ($i = count($this->positionBuffer) - 1; $i >= 0; $i--) {
            if ($this->positionBuffer[$i]['product_count'] === $productCount) {
                return $this->positionBuffer[$i];
            }
        }
        
        return null;
    }
    
    /**
     * Get position records within a range
     */
    public function getPositionRange(int $startProduct, int $endProduct): array
    {
        $results = [];
        
        foreach ($this->positionBuffer as $record) {
            $productCount = $record['product_count'];
            if ($productCount >= $startProduct && $productCount <= $endProduct) {
                $results[] = $record;
            }
        }
        
        return $results;
    }
    
    /**
     * Synchronize file handle position with XMLReader
     * This is a critical operation for maintaining position accuracy
     */
    public function synchronizeWithXmlReader(\XMLReader $reader): void
    {
        $this->stats['sync_operations']++;
        
        // XMLReader doesn't provide direct byte position access
        // We need to estimate based on our tracking
        
        // For now, we'll record the current file position
        // This is called at strategic points during parsing
        $this->recordPosition('xml_reader_sync');
    }
    
    /**
     * Read a chunk of data from current position
     */
    public function readChunk(int $size): string
    {
        if (!$this->fileHandle) {
            throw ResumeException::syncFailed("File handle not available for reading");
        }
        
        $chunk = fread($this->fileHandle, $size);
        if ($chunk === false) {
            throw ResumeException::syncFailed("Failed to read chunk from file");
        }
        
        $this->currentPosition += strlen($chunk);
        return $chunk;
    }
    
    /**
     * Peek at data without advancing position
     */
    public function peekChunk(int $size): string
    {
        if (!$this->fileHandle) {
            throw ResumeException::syncFailed("File handle not available for peeking");
        }
        
        $currentPos = ftell($this->fileHandle);
        $chunk = fread($this->fileHandle, $size);
        
        if ($chunk === false) {
            throw ResumeException::syncFailed("Failed to peek at file data");
        }
        
        // Restore position
        fseek($this->fileHandle, $currentPos);
        
        return $chunk;
    }
    
    /**
     * Get XML context around current position
     */
    public function getXmlContext(int $beforeBytes = 512, int $afterBytes = 512): string
    {
        if (!$this->fileHandle) {
            return '';
        }
        
        $currentPos = ftell($this->fileHandle);
        
        // Seek to before position
        $startPos = max(0, $currentPos - $beforeBytes);
        fseek($this->fileHandle, $startPos);
        
        // Read context
        $context = fread($this->fileHandle, $beforeBytes + $afterBytes);
        
        // Restore position
        fseek($this->fileHandle, $currentPos);
        
        return $context !== false ? $context : '';
    }
    
    /**
     * Clear position buffer
     */
    public function clearBuffer(): void
    {
        $this->positionBuffer = [];
    }
    
    /**
     * Get buffer contents
     */
    public function getBuffer(): array
    {
        return $this->positionBuffer;
    }
    
    /**
     * Get performance statistics
     */
    public function getStats(): array
    {
        return array_merge($this->stats, [
            'buffer_size' => count($this->positionBuffer),
            'current_position' => $this->currentPosition,
        ]);
    }
    
    /**
     * Export position history for debugging
     */
    public function exportPositionHistory(): array
    {
        return [
            'file_path' => $this->filePath,
            'current_position' => $this->currentPosition,
            'buffer_size' => count($this->positionBuffer),
            'positions' => $this->positionBuffer,
            'stats' => $this->getStats(),
        ];
    }
    
    /**
     * Close file handle and cleanup
     */
    public function close(): void
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
        
        $this->clearBuffer();
    }
    
    /**
     * Destructor to ensure cleanup
     */
    public function __destruct()
    {
        $this->close();
    }
    
    // Getters
    public function getFilePath(): string { return $this->filePath; }
    public function getBufferSize(): int { return $this->bufferSize; }
    public function isFileOpen(): bool { return $this->fileHandle !== null; }
}