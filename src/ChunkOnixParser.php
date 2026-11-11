<?php

namespace ONIXParser;

use ONIXParser\Exception\CheckpointException;
use ONIXParser\Resume\CheckpointStore;
use ONIXParser\Resume\FileCheckpointStore;

/**
 * Chunk-based ONIX Parser
 * 
 * Processes ONIX files in small chunks with exact byte position tracking
 * Perfect for large files (1GB+) with low memory usage and true resumable parsing
 */
class ChunkOnixParser
{
    /**
     * Callback return value: "I handled this record. Stop now. Resume AFTER it."
     * The checkpoint offset is the first byte after this record and the count includes it.
     */
    public const STOP_AFTER = 'onix-parser:stop-after';

    /**
     * Callback return value: "I did NOT handle this record. Stop now. Resume AT it."
     * The checkpoint offset is the first byte of this record and the count does not include it.
     * Use this when the callback sees a time budget or a signal before it has done any work on the record.
     */
    public const STOP_BEFORE = 'onix-parser:stop-before';

    /** @var string */
    private $filePath;
    
    /** @var CheckpointStore */
    private $checkpointStore;

    /** @var string|null Hash of the ONIX file given by the caller (stored in the checkpoint, never computed here) */
    private $callerHash = null;
    
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
    
    public function __construct(string $filePath, ?OnixParser $parser = null, ?Logger $logger = null)
    {
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }

        $this->filePath = $filePath;
        $this->checkpointStore = new FileCheckpointStore($filePath . '.chunk_checkpoint');
        $this->parser = $parser ?: new OnixParser($logger);
        $this->logger = $logger ?: new Logger();
        $this->fileSize = filesize($filePath);
        
        // Detect namespace from file header to ensure proper xpath operations
        $this->detectAndSetNamespace();
    }
    
    /**
     * Detect namespace from ONIX file header and configure parser
     */
    private function detectAndSetNamespace(): void
    {
        $handle = fopen($this->filePath, 'rb');
        if (!$handle) {
            return;
        }
        
        // Read first 2KB to find ONIXMessage element and its namespace
        $header = fread($handle, 2048);
        fclose($handle);
        
        // Look for xmlns attribute in ONIXMessage
        if (preg_match('/xmlns\s*=\s*["\']([^"\']+)["\']/i', $header, $matches)) {
            $namespaceURI = $matches[1];
            
            // Set namespace on the parser using reflection to access protected properties
            $reflection = new \ReflectionClass($this->parser);
            $hasNamespaceProperty = $reflection->getProperty('hasNamespace');
            $namespaceURIProperty = $reflection->getProperty('namespaceURI');
            
            $hasNamespaceProperty->setAccessible(true);
            $namespaceURIProperty->setAccessible(true);
            
            $hasNamespaceProperty->setValue($this->parser, true);
            $namespaceURIProperty->setValue($this->parser, $namespaceURI);
            
            $this->logger->info("ChunkOnixParser detected namespace: $namespaceURI");
        }
    }

    /**
     * Set a custom checkpoint filename
     * Useful for avoiding conflicts between concurrent parsing jobs
     *
     * @param string $checkpointFile Full path to checkpoint file
     * @return void
     */
    public function setCheckpointFile(string $checkpointFile): void
    {
        $this->checkpointStore = new FileCheckpointStore($checkpointFile);
    }

    /**
     * Keep the checkpoint somewhere else than a file (for example in the database, in the
     * same transaction as the records). See CheckpointStore.
     */
    public function setCheckpointStore(CheckpointStore $store): void
    {
        $this->checkpointStore = $store;
    }

    public function getCheckpointStore(): CheckpointStore
    {
        return $this->checkpointStore;
    }

    /**
     * Hash of the ONIX file, computed by the caller (for example the sha256 it already has in its file
     * registry). It is stored in each checkpoint and compared on resume. The parser never hashes the file.
     * When a hash is set and the checkpoint has one, the hash alone decides; otherwise size + mtime decide.
     */
    public function setFileHash(?string $hash): void
    {
        $this->callerHash = $hash;
    }

    /**
     * Parse ONIX file with chunk-based processing and automatic checkpoints.
     *
     * CALLBACK CONTRACT: callback(Product $product, int $index): mixed
     *   - Any normal return value (true, null, an array, 0 ...) means "handled, continue".
     *   - ChunkOnixParser::STOP_AFTER  = handled, stop, resume after this record.
     *   - ChunkOnixParser::STOP_BEFORE = NOT handled, stop, resume at this record.
     *   - false is the old contract and means STOP_AFTER (kept so old callers behave as before).
     *     A caller that returns false before it has handled the record loses that record. Use STOP_BEFORE.
     *   - If the callback throws, the exception goes up. The record is not counted and the last checkpoint
     *     stays; records after that checkpoint are delivered again on resume (at-least-once).
     *
     * Checkpoint offset rule: a checkpoint always points at a record boundary, so a resume never
     * loses or repeats a record that the callback finished before the checkpoint was saved.
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
        $earlyTermination = false; // Track if callback requested early stop
        $finalPosition = 0; // Track final position before file close
        $lastFilePosition = 0; // Track file position during parsing
        
        try {
            while (!feof($this->fileHandle)) {
                $chunk = fread($this->fileHandle, $this->chunkSize);
                if ($chunk === false) {
                    break;
                }
                
                $buffer .= $chunk;
                $currentPosition = ftell($this->fileHandle);
                $lastFilePosition = $currentPosition; // Track the last known position
                
                // Process complete products in buffer
                $lastEndPos = 0;
                while (($startPos = $this->findNextProductTag($buffer, $lastEndPos)) !== false) {
                    // Find matching closing tag
                    $endPos = $this->findClosingProductTag($buffer, $startPos);
                    
                    if ($endPos === false) {
                        // Incomplete product, need more data
                        break;
                    }
                    
                    $productXml = substr($buffer, $startPos, $endPos - $startPos + 1); // +1 to include the '>' character
                    
                    try {
                        // Convert XML to Product object using OnixParser  
                        $product = $this->parser->parseProductStreaming($this->nodeFromXml($productXml));
                        
                        $recordStart = $currentPosition - strlen($buffer) + $startPos;
                        $result = call_user_func($callback, $product, $productCount);
                        $processedProducts[] = $result;

                        // STOP_BEFORE: the callback did not handle this record. Offset = start of the record.
                        if ($result === self::STOP_BEFORE) {
                            $this->logger->info("Callback requested stop BEFORE record #$productCount (not handled)");
                            $earlyTermination = true;
                            $finalPosition = $recordStart;
                            break 2; // Break both loops
                        }

                        $productCount++;
                        
                        // STOP_AFTER (or the old 'false'): the record is handled. Offset = first byte after it.
                        if ($result === self::STOP_AFTER || $result === false) {
                            $this->logger->info("Callback requested stop AFTER record #$productCount (handled)");
                            $earlyTermination = true;
                            $finalPosition = $currentPosition - strlen($buffer) + $endPos + 1;
                            break 2; // Break both loops
                        }
                        
                        // Save checkpoint
                        if ($productCount % $checkpointInterval === 0) {
                            $checkpointPos = $currentPosition - strlen($buffer) + $endPos + 1;
                            $this->saveCheckpoint($checkpointPos, $productCount);
                            
                            $elapsed = round(microtime(true) - $startTime, 2);
                            $progress = round(($checkpointPos / $this->fileSize) * 100, 2);
                            $rate = $elapsed > 0 ? round($productCount / $elapsed) : $productCount;
                            
                            $this->logger->info("Checkpoint saved at product {$productCount} - Progress: {$progress}% ({$rate} products/sec)");
                        }
                        
                        if ($productCount % 500 === 0) {
                            $memoryMB = round(memory_get_usage() / 1024 / 1024, 2);
                            $this->logger->debug("Processed $productCount products - Memory: {$memoryMB}MB");
                        }
                        
                    } catch (\Exception $e) {
                        $this->logger->error("Error processing product #$productCount at buffer position $startPos-$endPos: " . $e->getMessage());
                        $this->logger->debug("Problematic XML fragment: " . substr($productXml, 0, 200) . "...");
                        throw $e;
                    }
                    
                    $lastEndPos = $endPos + 1; // TESTING: Changed from +10 to +1 to avoid skipping products
                }
                
                // Keep unprocessed part of buffer
                if ($lastEndPos > 0) {
                    $buffer = substr($buffer, $lastEndPos);
                }
                
                // Prevent buffer from growing too large - but preserve potential Product boundaries
                if (strlen($buffer) > $this->chunkSize * 3) {
                    // Instead of aggressive truncation, find a safe cut point after a complete Product
                    $safeEndPos = $this->findLastCompleteProductEnd($buffer, $this->chunkSize);
                    if ($safeEndPos > 0) {
                        $buffer = substr($buffer, $safeEndPos);
                        $this->logger->debug("Buffer trimmed safely at position $safeEndPos to prevent memory issues");
                    } else {
                        // Fallback: keep more buffer if we can't find a safe cut point
                        $keepSize = $this->chunkSize * 2;
                        $buffer = substr($buffer, -$keepSize);
                        $this->logger->warning("Used fallback buffer trimming - may affect parsing");
                    }
                }
            }
            
        } finally {
            fclose($this->fileHandle);
        }
        
        // Only clear checkpoint if we reached EOF naturally (no early termination)
        if (!$earlyTermination && $this->checkpointStore->load() !== null) {
            $this->logger->info("File parsing completed naturally - reached position " . number_format($lastFilePosition) . " of " . number_format($this->fileSize) . " bytes");
            
            // Additional safety check: verify we actually reached the end
            if ($lastFilePosition >= $this->fileSize) {
                $this->logger->info("Confirmed EOF reached - clearing checkpoint");
                $this->checkpointStore->clear();
            } else {
                $this->logger->warning("Parsing stopped but EOF not reached - preserving checkpoint for investigation");
                $this->saveCheckpoint($lastFilePosition, $productCount);
            }
        } elseif ($earlyTermination && $finalPosition > 0) {
            // Save final checkpoint for continuation
            $this->saveCheckpoint($finalPosition, $productCount);
            $this->logger->info("Early termination - checkpoint preserved for continuation at position: " . number_format($finalPosition));
        }
        
        $totalTime = round(microtime(true) - $startTime, 2);
        $avgRate = $totalTime > 0 ? round($productCount / $totalTime) : $productCount;
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
                    $productXml = substr($buffer, $startPos, $endPos - $startPos + 1);
                    
                    // Skip products before offset
                    if ($totalProductCount <= $offset) {
                        $lastEndPos = $endPos + 1;
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
                            $rate = $elapsed > 0 ? round($processedCount / $elapsed) : $processedCount;
                            $this->logger->info("Processed $processedCount products (current: #$totalProductCount) - {$rate} products/sec");
                        }
                        
                    } catch (\Exception $e) {
                        $this->logger->error("Error processing product #$totalProductCount: " . $e->getMessage());
                        throw $e;
                    }
                    
                    $lastEndPos = $endPos + 1; // TESTING: Changed from +10 to +1 to avoid skipping products
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
                    $productXml = substr($buffer, $startPos, $endPos - $startPos + 1);
                    
                    // Cache position every 100 products
                    if ($totalProductCount % 100 === 0) {
                        $bytePos = $currentPosition - strlen($buffer) + $startPos;
                        $positionCache[$totalProductCount] = $bytePos;
                    }
                    
                    // Skip products before offset
                    if ($totalProductCount <= $offset) {
                        $lastEndPos = $endPos + 1;
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
                    
                    $lastEndPos = $endPos + 1; // TESTING: Changed from +10 to +1 to avoid skipping products
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
     * Find the last complete Product end position for safe buffer trimming
     */
    private function findLastCompleteProductEnd(string $buffer, int $maxKeepSize): int
    {
        $searchStart = max(0, strlen($buffer) - $maxKeepSize);
        $lastProductEnd = 0;
        
        // Look for complete </Product> tags in the trim candidate area
        $pos = $searchStart;
        while (($closePos = strpos($buffer, '</Product>', $pos)) !== false) {
            $lastProductEnd = $closePos + 10; // +10 for '</Product>'
            $pos = $closePos + 1;
        }
        
        return $lastProductEnd;
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
                        return $pos; // Return position of '>' in </Product>
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
     * Get total product count (accurate, scans entire file properly)
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
        $overlap = 1024; // Keep 1KB overlap to avoid missing products at chunk boundaries
        
        try {
            while (!feof($handle)) {
                $chunk = fread($handle, $this->chunkSize);
                if ($chunk === false) {
                    break;
                }
                
                $buffer .= $chunk;
                
                // Process complete products in buffer using same logic as parsing
                $lastEndPos = 0;
                while (($startPos = $this->findNextProductTag($buffer, $lastEndPos)) !== false) {
                    $endPos = $this->findClosingProductTag($buffer, $startPos);
                    
                    if ($endPos === false) {
                        // Incomplete product, need more data
                        break;
                    }
                    
                    $productCount++;
                    $lastEndPos = $endPos + 1;
                }
                
                // Keep unprocessed part of buffer with overlap to avoid missing products
                if ($lastEndPos > 0) {
                    $buffer = substr($buffer, $lastEndPos);
                } else {
                    // No complete products found, keep overlap
                    if (strlen($buffer) > $overlap) {
                        $buffer = substr($buffer, -$overlap);
                    }
                }
            }
        } finally {
            fclose($handle);
        }
        
        $this->logger->info("Found exactly $productCount products in file");
        return $productCount;
    }
    
    private function saveCheckpoint(int $position, int $count): void
    {
        // No hash of the file here (it cost 19.5 s of 60 s on 401 MB). Size + mtime + the caller's hash.
        $this->checkpointStore->save([
            'version' => 2,
            'position' => $position,
            'count' => $count,
            'timestamp' => time(),
            'file_size' => $this->fileSize,
            'file_mtime' => filemtime($this->filePath),
            'caller_hash' => $this->callerHash,
            'chunk_size' => $this->chunkSize,
        ]);
    }

    /**
     * Load and check the checkpoint.
     *
     * @return array|null null = no checkpoint (fresh start)
     * @throws CheckpointException damaged checkpoint, or the ONIX file is not the one it was made for
     */
    private function loadCheckpoint(): ?array
    {
        $checkpoint = $this->checkpointStore->load();
        if ($checkpoint === null) {
            return null;
        }

        // Size must always match (old checkpoints have it too)
        if (isset($checkpoint['file_size']) && (int)$checkpoint['file_size'] !== $this->fileSize) {
            throw CheckpointException::fileIntegrityFailed($this->filePath);
        }

        if (isset($checkpoint['position']) && $checkpoint['position'] > $this->fileSize) {
            throw CheckpointException::validationFailed("position {$checkpoint['position']} is beyond the end of the file ($this->fileSize bytes)");
        }

        // Identity: the caller's hash decides when both sides have one, else the modification time
        if ($this->callerHash !== null && !empty($checkpoint['caller_hash'])) {
            if (!hash_equals((string)$checkpoint['caller_hash'], $this->callerHash)) {
                throw CheckpointException::fileIntegrityFailed($this->filePath);
            }
        } elseif (isset($checkpoint['file_mtime']) && (int)$checkpoint['file_mtime'] !== filemtime($this->filePath)) {
            throw CheckpointException::fileIntegrityFailed($this->filePath);
        }

        return $checkpoint;
    }
    
    /**
     * Clear any existing checkpoint
     */
    public function clearCheckpoint(): void
    {
        $this->checkpointStore->clear();
    }
    
    /**
     * Get checkpoint information (null = none). Throws CheckpointException when it is damaged or
     * does not belong to this file.
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
        
        // Add ONIX namespace if missing (minimal fix for extracted fragments)
        if (strpos($xml, 'xmlns') === false && strpos($xml, '<Product>') !== false) {
            $xml = str_replace('<Product>', '<Product xmlns="http://www.editeur.org/onix/3.0/reference">', $xml);
        }
        
        if (!$dom->loadXML($xml)) {
            throw new \Exception("Failed to parse XML fragment");
        }
        
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