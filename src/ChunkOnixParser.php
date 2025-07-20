<?php

namespace ONIXParser;

/**
 * Chunk-based ONIX Parser
 * 
 * Processes ONIX files in small chunks with exact byte position tracking
 * Perfect for large files (1GB+) with low memory usage and true resumable parsing
 */
class ChunkOnixParser
{
    /** @var string */
    private $filePath;
    
    /** @var string */
    private $checkpointFile;
    
    /** @var Logger */
    private $logger;
    
    /** @var OnixParser */
    private $parser;
    
    /** @var int */
    private $chunkSize = 512 * 1024; // 512KB chunks
    
    /** @var resource */
    private $fileHandle;
    
    /** @var int */
    private $fileSize;
    
    public function __construct(string $filePath, OnixParser $parser = null, Logger $logger = null)
    {
        $this->filePath = $filePath;
        $this->checkpointFile = $filePath . '.chunk_checkpoint';
        $this->parser = $parser ?: new OnixParser($logger);
        $this->logger = $logger ?: new Logger();
        $this->fileSize = filesize($filePath);
        
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }
    }
    
    /**
     * Parse ONIX file with chunk-based processing and automatic checkpoints
     */
    public function parseWithCheckpoints(callable $callback, int $checkpointInterval = 1000): array
    {
        $this->logger->info("Starting chunk-based ONIX parsing: " . basename($this->filePath));
        $this->logger->info("File size: " . number_format($this->fileSize) . " bytes, Chunk size: " . number_format($this->chunkSize) . " bytes");
        
        // Load checkpoint
        $checkpoint = $this->loadCheckpoint();
        $startPosition = $checkpoint['position'] ?? 0;
        $productCount = $checkpoint['count'] ?? 0;
        
        $this->logger->info("Resume from position: " . number_format($startPosition) . " bytes, product: $productCount");
        
        $this->fileHandle = fopen($this->filePath, 'rb');
        if (!$this->fileHandle) {
            throw new \Exception("Cannot open file: {$this->filePath}");
        }
        
        fseek($this->fileHandle, $startPosition);
        
        $buffer = '';
        $processedProducts = [];
        $startTime = microtime(true);
        
        try {
            while (!feof($this->fileHandle)) {
                $chunk = fread($this->fileHandle, $this->chunkSize);
                if ($chunk === false) {
                    break;
                }
                
                $buffer .= $chunk;
                $currentPosition = ftell($this->fileHandle);
                
                // Process complete products in buffer
                $lastEndPos = 0;
                while (($startPos = $this->findNextProductTag($buffer, $lastEndPos)) !== false) {
                    // Find matching closing tag
                    $endPos = $this->findClosingProductTag($buffer, $startPos);
                    
                    if ($endPos === false) {
                        // Incomplete product, need more data
                        break;
                    }
                    
                    $productXml = substr($buffer, $startPos, $endPos - $startPos + 10); // +10 for </Product>
                    
                    try {
                        // Convert XML to Product object using OnixParser  
                        $product = $this->parser->parseProductStreaming($this->nodeFromXml($productXml));
                        
                        $result = call_user_func($callback, $product, $productCount);
                        $processedProducts[] = $result;
                        $productCount++;
                        
                        // Save checkpoint
                        if ($productCount % $checkpointInterval === 0) {
                            $checkpointPos = $currentPosition - strlen($buffer) + $endPos + 10;
                            $this->saveCheckpoint($checkpointPos, $productCount);
                            
                            $elapsed = round(microtime(true) - $startTime, 2);
                            $progress = round(($checkpointPos / $this->fileSize) * 100, 2);
                            $rate = round($productCount / $elapsed);
                            
                            $this->logger->info("Checkpoint saved at product {$productCount} - Progress: {$progress}% ({$rate} products/sec)");
                        }
                        
                        if ($productCount % 500 === 0) {
                            $memoryMB = round(memory_get_usage() / 1024 / 1024, 2);
                            $this->logger->debug("Processed $productCount products - Memory: {$memoryMB}MB");
                        }
                        
                    } catch (\Exception $e) {
                        $this->logger->error("Error processing product #$productCount: " . $e->getMessage());
                        throw $e;
                    }
                    
                    $lastEndPos = $endPos + 10;
                }
                
                // Keep unprocessed part of buffer
                if ($lastEndPos > 0) {
                    $buffer = substr($buffer, $lastEndPos);
                }
                
                // Prevent buffer from growing too large
                if (strlen($buffer) > $this->chunkSize * 2) {
                    $keepSize = $this->chunkSize;
                    $buffer = substr($buffer, -$keepSize);
                }
            }
            
        } finally {
            fclose($this->fileHandle);
        }
        
        // Clear checkpoint on completion
        if (file_exists($this->checkpointFile)) {
            unlink($this->checkpointFile);
        }
        
        $totalTime = round(microtime(true) - $startTime, 2);
        $avgRate = round($productCount / $totalTime);
        $this->logger->info("Chunk parsing completed: $productCount products in {$totalTime}s (avg: {$avgRate} products/sec)");
        
        return $processedProducts;
    }
    
    /**
     * Parse with offset and limit for batch processing
     */
    public function parseWithLimits(callable $callback, int $offset = 0, int $limit = 0): array
    {
        $this->logger->info("Chunk parsing with offset: $offset, limit: $limit");
        
        // For small offsets (< 50), use sequential processing 
        // For larger offsets, use position cache to seek efficiently
        if ($offset > 50) {
            return $this->parseWithSeek($callback, $offset, $limit);
        }
        
        $this->fileHandle = fopen($this->filePath, 'rb');
        if (!$this->fileHandle) {
            throw new \Exception("Cannot open file: {$this->filePath}");
        }
        
        $buffer = '';
        $processedProducts = [];
        $totalProductCount = 0;
        $processedCount = 0;
        $startTime = microtime(true);
        
        try {
            while (!feof($this->fileHandle) && ($limit == 0 || $processedCount < $limit)) {
                $chunk = fread($this->fileHandle, $this->chunkSize);
                if ($chunk === false) {
                    break;
                }
                
                $buffer .= $chunk;
                $currentPosition = ftell($this->fileHandle);
                
                // Process complete products in buffer
                $lastEndPos = 0;
                while (($startPos = $this->findNextProductTag($buffer, $lastEndPos)) !== false) {
                    $endPos = $this->findClosingProductTag($buffer, $startPos);
                    
                    if ($endPos === false) {
                        break; // Need more data
                    }
                    
                    $totalProductCount++;
                    $productXml = substr($buffer, $startPos, $endPos - $startPos + 10);
                    
                    // Skip products before offset
                    if ($totalProductCount <= $offset) {
                        $lastEndPos = $endPos + 10;
                        continue;
                    }
                    
                    // Stop if limit reached
                    if ($limit > 0 && $processedCount >= $limit) {
                        break 2; // Break both loops
                    }
                    
                    try {
                        $bytePosition = $currentPosition - strlen($buffer) + $startPos;
                        
                        // Convert XML to Product object using OnixParser
                        $product = $this->parser->parseProductStreaming($this->nodeFromXml($productXml));
                        
                        $result = call_user_func($callback, $product, $totalProductCount, $bytePosition);
                        $processedProducts[] = $result;
                        $processedCount++;
                        
                        // Check if callback wants to stop processing
                        if ($result === false) {
                            $this->logger->info("Callback requested early termination at product #$totalProductCount");
                            break 2; // Break both loops
                        }
                        
                        if ($processedCount % 100 === 0) {
                            $elapsed = round(microtime(true) - $startTime, 2);
                            $rate = round($processedCount / $elapsed);
                            $this->logger->info("Processed $processedCount products (current: #$totalProductCount) - {$rate} products/sec");
                        }
                        
                    } catch (\Exception $e) {
                        $this->logger->error("Error processing product #$totalProductCount: " . $e->getMessage());
                        throw $e;
                    }
                    
                    $lastEndPos = $endPos + 10;
                }
                
                // Keep unprocessed part of buffer
                if ($lastEndPos > 0) {
                    $buffer = substr($buffer, $lastEndPos);
                }
            }
            
        } finally {
            fclose($this->fileHandle);
        }
        
        $totalTime = round(microtime(true) - $startTime, 2);
        $this->logger->info("Batch parsing completed: $processedCount products processed in {$totalTime}s");
        
        return $processedProducts;
    }
    
    /**
     * Optimized parsing for large offsets using position cache
     */
    private function parseWithSeek(callable $callback, int $offset, int $limit): array
    {
        $positionCacheFile = $this->filePath . '.position_cache';
        $positionCache = $this->loadPositionCache($positionCacheFile);
        
        // Find the best starting position from cache
        $startPosition = 0;
        $startProductCount = 0;
        
        foreach ($positionCache as $productNum => $position) {
            if ($productNum <= $offset && $productNum > $startProductCount) {
                $startPosition = $position;
                $startProductCount = $productNum;
            }
        }
        
        $this->logger->info("Seeking to byte position $startPosition (product ~$startProductCount) for offset $offset");
        
        $this->fileHandle = fopen($this->filePath, 'rb');
        if (!$this->fileHandle) {
            throw new \Exception("Cannot open file: {$this->filePath}");
        }
        
        // Seek to approximately the right position
        fseek($this->fileHandle, $startPosition);
        
        $buffer = '';
        $processedProducts = [];
        $totalProductCount = $startProductCount;
        $processedCount = 0;
        $startTime = microtime(true);
        
        try {
            while (!feof($this->fileHandle) && ($limit == 0 || $processedCount < $limit)) {
                $chunk = fread($this->fileHandle, $this->chunkSize);
                if ($chunk === false) {
                    break;
                }
                
                $buffer .= $chunk;
                $currentPosition = ftell($this->fileHandle);
                
                // Process complete products in buffer
                $lastEndPos = 0;
                while (($startPos = $this->findNextProductTag($buffer, $lastEndPos)) !== false) {
                    $endPos = $this->findClosingProductTag($buffer, $startPos);
                    
                    if ($endPos === false) {
                        break; // Need more data
                    }
                    
                    $totalProductCount++;
                    $productXml = substr($buffer, $startPos, $endPos - $startPos + 10);
                    
                    // Cache position every 100 products
                    if ($totalProductCount % 100 === 0) {
                        $bytePos = $currentPosition - strlen($buffer) + $startPos;
                        $positionCache[$totalProductCount] = $bytePos;
                    }
                    
                    // Skip products before offset
                    if ($totalProductCount <= $offset) {
                        $lastEndPos = $endPos + 10;
                        continue;
                    }
                    
                    // Stop if limit reached
                    if ($limit > 0 && $processedCount >= $limit) {
                        break 2; // Break both loops
                    }
                    
                    try {
                        $bytePosition = $currentPosition - strlen($buffer) + $startPos;
                        
                        // Convert XML to Product object using OnixParser
                        $product = $this->parser->parseProductStreaming($this->nodeFromXml($productXml));
                        
                        $result = call_user_func($callback, $product, $totalProductCount, $bytePosition);
                        $processedProducts[] = $result;
                        $processedCount++;
                        
                        // Check if callback wants to stop processing
                        if ($result === false) {
                            $this->logger->info("Callback requested early termination at product #$totalProductCount");
                            break 2; // Break both loops
                        }
                        
                    } catch (\Exception $e) {
                        $this->logger->error("Error processing product #$totalProductCount: " . $e->getMessage());
                        throw $e;
                    }
                    
                    $lastEndPos = $endPos + 10;
                }
                
                // Keep unprocessed part of buffer
                if ($lastEndPos > 0) {
                    $buffer = substr($buffer, $lastEndPos);
                }
            }
            
        } finally {
            fclose($this->fileHandle);
        }
        
        // Save updated position cache
        $this->savePositionCache($positionCacheFile, $positionCache);
        
        $totalTime = round(microtime(true) - $startTime, 2);
        $this->logger->info("Seek-based parsing completed: $processedCount products processed in {$totalTime}s");
        
        return $processedProducts;
    }
    
    /**
     * Load position cache for efficient seeking
     */
    private function loadPositionCache(string $cacheFile): array
    {
        if (!file_exists($cacheFile)) {
            return [];
        }
        
        $data = file_get_contents($cacheFile);
        $cache = json_decode($data, true);
        
        return is_array($cache) ? $cache : [];
    }
    
    /**
     * Save position cache for future use
     */
    private function savePositionCache(string $cacheFile, array $cache): void
    {
        // Keep only every 100th position to prevent cache from growing too large
        $filtered = [];
        foreach ($cache as $productNum => $position) {
            if ($productNum % 100 === 0) {
                $filtered[$productNum] = $position;
            }
        }
        
        file_put_contents($cacheFile, json_encode($filtered));
    }
    
    /**
     * Find the next exact <Product> tag (not ProductIdentifier, ProductComposition, etc.)
     */
    private function findNextProductTag(string $buffer, int $startPos): int|false
    {
        $pos = $startPos;
        $len = strlen($buffer);
        
        while ($pos < $len) {
            $productPos = strpos($buffer, '<Product', $pos);
            if ($productPos === false) {
                return false;
            }
            
            // Check if this is an exact Product tag
            $afterProduct = $productPos + 8; // strlen('<Product')
            if ($afterProduct < $len) {
                $nextChar = $buffer[$afterProduct];
                if ($nextChar === '>' || $nextChar === ' ' || $nextChar === "\t" || $nextChar === "\n") {
                    return $productPos;
                }
            }
            
            $pos = $productPos + 1;
        }
        
        return false;
    }
    
    /**
     * Find the closing </Product> tag matching the opening tag
     */
    private function findClosingProductTag(string $buffer, int $startPos): int|false
    {
        $depth = 0;
        $pos = $startPos;
        $len = strlen($buffer);
        
        // Handle both <Product> and namespaced <x:Product> tags
        $inTag = false;
        $tagContent = '';
        
        while ($pos < $len) {
            $char = $buffer[$pos];
            
            if ($char === '<') {
                $inTag = true;
                $tagContent = '<';
            } elseif ($char === '>' && $inTag) {
                $tagContent .= '>';
                $inTag = false;
                
                // Check if this is a Product tag (exact match, not ProductIdentifier etc.)
                if (preg_match('/^<(\w*:)?Product(\s|>)/i', $tagContent)) {
                    $depth++;
                } elseif (preg_match('/^<\/(\w*:)?Product>$/i', $tagContent)) {
                    $depth--;
                    if ($depth === 0) {
                        return $pos;
                    }
                }
                
                $tagContent = '';
            } elseif ($inTag) {
                $tagContent .= $char;
            }
            
            $pos++;
        }
        
        return false; // No complete closing tag found
    }
    
    /**
     * Get total product count (approximate, scans in chunks)
     */
    public function getProductCount(): int
    {
        $this->logger->info("Counting products in file...");
        
        $handle = fopen($this->filePath, 'rb');
        if (!$handle) {
            throw new \Exception("Cannot open file: {$this->filePath}");
        }
        
        $productCount = 0;
        $buffer = '';
        
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $this->chunkSize);
                if ($chunk === false) {
                    break;
                }
                
                $buffer .= $chunk;
                
                // Count <Product> opening tags
                $count = preg_match_all('/<(\w*:)?Product\b/i', $buffer);
                $productCount += $count;
                
                // Keep only the last part of buffer to avoid double-counting
                if (strlen($buffer) > $this->chunkSize) {
                    $buffer = substr($buffer, -1024); // Keep last 1KB
                }
            }
        } finally {
            fclose($handle);
        }
        
        $this->logger->info("Found approximately $productCount products");
        return $productCount;
    }
    
    private function saveCheckpoint(int $position, int $count): void
    {
        $checkpoint = [
            'position' => $position,
            'count' => $count,
            'timestamp' => time(),
            'file_size' => $this->fileSize,
            'file_hash' => md5_file($this->filePath),
            'chunk_size' => $this->chunkSize
        ];
        
        file_put_contents($this->checkpointFile, json_encode($checkpoint, JSON_PRETTY_PRINT));
    }
    
    private function loadCheckpoint(): ?array
    {
        if (!file_exists($this->checkpointFile)) {
            return null;
        }
        
        $checkpoint = json_decode(file_get_contents($this->checkpointFile), true);
        
        // Validate checkpoint is still valid
        if ($checkpoint && isset($checkpoint['file_hash'])) {
            $currentHash = md5_file($this->filePath);
            if ($checkpoint['file_hash'] !== $currentHash) {
                $this->logger->warning("Checkpoint file hash mismatch - file may have changed, starting fresh");
                unlink($this->checkpointFile);
                return null;
            }
        }
        
        return $checkpoint;
    }
    
    /**
     * Clear any existing checkpoint
     */
    public function clearCheckpoint(): void
    {
        if (file_exists($this->checkpointFile)) {
            unlink($this->checkpointFile);
        }
    }
    
    /**
     * Get checkpoint information
     */
    public function getCheckpointInfo(): ?array
    {
        return $this->loadCheckpoint();
    }
    
    /**
     * Set chunk size (for performance tuning)
     */
    public function setChunkSize(int $bytes): void
    {
        $this->chunkSize = $bytes;
        $this->logger->info("Chunk size set to: " . number_format($bytes) . " bytes");
    }
    
    /**
     * Get parsing statistics
     */
    public function getStats(): array
    {
        $checkpoint = $this->getCheckpointInfo();
        
        return [
            'file_path' => $this->filePath,
            'file_size' => $this->fileSize,
            'chunk_size' => $this->chunkSize,
            'has_checkpoint' => $checkpoint !== null,
            'checkpoint_info' => $checkpoint
        ];
    }
    
    /**
     * Convert XML string to DOMNode for OnixParser
     */
    private function nodeFromXml(string $xml): \DOMNode
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        return $dom->documentElement;
    }
    
    /**
     * Convenience wrapper returning a ready Onix object (like parseFileStreaming)
     */
    public function parseIntoOnix(int $offset = 0, int $limit = 0): \ONIXParser\Model\Onix
    {
        $onix = new \ONIXParser\Model\Onix();
        $this->parseWithLimits(
            function (\ONIXParser\Model\Product $product) use ($onix) { 
                $onix->setProduct($product); 
                return $product;
            },
            $offset,
            $limit
        );
        return $onix;
    }
}