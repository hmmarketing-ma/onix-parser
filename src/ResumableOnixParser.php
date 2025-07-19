<?php

namespace ONIXParser;

use ONIXParser\Resume\CheckpointManager;
use ONIXParser\Resume\FilePositionTracker;
use ONIXParser\Resume\ParserState;
use ONIXParser\Resume\ResumePoint;
use ONIXParser\Exception\CheckpointException;
use ONIXParser\Exception\ResumeException;
use ONIXParser\Model\Onix;

/**
 * Resumable ONIX Parser with true file position resume capability
 * Extends OnixParser to add checkpoint and resume functionality
 */
class ResumableOnixParser extends OnixParser
{
    /** @var CheckpointManager */
    private $checkpointManager;
    
    /** @var FilePositionTracker */
    private $positionTracker;
    
    /** @var bool Whether checkpoints are enabled */
    private $checkpointsEnabled = false;
    
    /** @var int Checkpoint interval (products) */
    private $checkpointInterval = 100;
    
    /** @var string Current checkpoint ID */
    private $currentCheckpointId;
    
    /** @var bool Whether we're resuming from a checkpoint */
    private $isResuming = false;
    
    /** @var ResumePoint Current resume point */
    private $currentResumePoint;
    
    /** @var int Products processed in current session */
    private $sessionProductCount = 0;
    
    /** @var int Last checkpoint product count */
    private $lastCheckpointCount = 0;
    
    /** @var array Resume options */
    private $resumeOptions = [];
    
    public function __construct(Logger $logger = null, CheckpointManager $checkpointManager = null)
    {
        parent::__construct($logger);
        $this->checkpointManager = $checkpointManager ?: new CheckpointManager(null, $this->logger);
    }
    
    /**
     * Enhanced streaming parser with resume capability
     */
    public function parseFileStreaming(string $xmlPath, array $options = []): Onix
    {
        // Merge with resume-specific options
        $this->resumeOptions = array_merge([
            'enable_checkpoints' => false,
            'checkpoint_interval' => 100,
            'checkpoint_id' => null,
            'resume_from_checkpoint' => null,
            'auto_resume' => true,
            'checkpoint_dir' => null,
        ], $options);
        
        $this->checkpointsEnabled = $this->resumeOptions['enable_checkpoints'];
        $this->checkpointInterval = $this->resumeOptions['checkpoint_interval'];
        $this->currentCheckpointId = $this->resumeOptions['checkpoint_id'] ?: $this->generateCheckpointId($xmlPath);
        
        // Check for existing checkpoint or explicit resume
        if ($this->shouldResumeFromCheckpoint($xmlPath)) {
            return $this->resumeFromCheckpoint($xmlPath);
        }
        
        // Start fresh parsing with checkpoint support
        return $this->parseFileWithCheckpoints($xmlPath, $options);
    }
    
    /**
     * Parse file with checkpoint support
     */
    private function parseFileWithCheckpoints(string $xmlPath, array $options): Onix
    {
        $this->xmlPath = $xmlPath;
        
        if (!file_exists($xmlPath)) {
            throw new \Exception("XML file not found: $xmlPath");
        }
        
        // Initialize position tracker
        $this->positionTracker = new FilePositionTracker($xmlPath);
        
        // Set default options
        $options = array_merge([
            'limit' => 0,
            'offset' => 0,
            'callback' => null,
            'continue_on_error' => true,
        ], $options);
        
        // Create a new Onix object
        $this->onix = new Onix();
        
        // Enable user error handling
        $previous = libxml_use_internal_errors(true);
        
        try {
            // Create XMLReader instance
            $reader = new \XMLReader();
            
            // Open the XML file
            if (!$reader->open($xmlPath)) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new \Exception('Failed to open XML file: ' . $this->formatLibXMLErrors($errors));
            }
            
            // Variables to track progress
            $productCount = 0;
            $processedCount = 0;
            $skippedCount = 0;
            $headerProcessed = false;
            $versionDetected = false;
            
            // Read the XML file
            while ($reader->read()) {
                // Record position for important elements
                $this->positionTracker->synchronizeWithXmlReader($reader);
                
                // Process only element nodes
                if ($reader->nodeType !== \XMLReader::ELEMENT) {
                    continue;
                }
                
                // Check for namespace
                if (!$headerProcessed && $reader->namespaceURI) {
                    $this->hasNamespace = true;
                    $this->namespaceURI = $reader->namespaceURI;
                    $this->logger->info("XML has namespace: " . $this->namespaceURI);
                }
                
                // Detect ONIX version from root element
                if (!$versionDetected && ($reader->name === 'ONIXMessage' || $reader->localName === 'ONIXMessage')) {
                    $release = $reader->getAttribute('release');
                    if ($release) {
                        $this->onix->setVersion('3.' . $release);
                    } else {
                        $this->onix->setVersion('3.0');
                    }
                    $versionDetected = true;
                }
                
                // Process header information
                if (!$headerProcessed && 
                    ($reader->name === 'Header' || $reader->localName === 'Header')) {
                    
                    // Record position before header processing
                    $this->positionTracker->recordPosition('header_start');
                    
                    $headerNode = $this->getNodeFromReader($reader);
                    if ($headerNode) {
                        $header = $this->parseHeaderFromNode($headerNode);
                        $this->onix->setHeader($header);
                        $headerProcessed = true;
                    }
                }
                
                // Process product elements
                if ($reader->name === 'Product' || $reader->localName === 'Product') {
                    $productCount++;
                    
                    // Record position before product processing
                    $this->positionTracker->recordPosition('product_start', $productCount);
                    
                    // Skip products based on offset
                    if ($productCount <= $options['offset']) {
                        $skippedCount++;
                        $reader->next();
                        continue;
                    }
                    
                    // Check if we've reached the limit
                    if ($options['limit'] > 0 && $processedCount >= $options['limit']) {
                        break;
                    }
                    
                    // Create checkpoint if needed
                    if ($this->shouldCreateCheckpoint($productCount)) {
                        $this->createCheckpoint($productCount, $processedCount, $skippedCount, $headerProcessed, $versionDetected);
                    }
                    
                    try {
                        // Parse the product
                        $productNode = $this->getNodeFromReader($reader);
                        if ($productNode) {
                            $product = $this->parseProductStreaming($productNode);
                            $this->onix->setProduct($product);
                            
                            // Call callback if provided
                            if (is_callable($options['callback'])) {
                                $callbackResult = call_user_func($options['callback'], $product, $processedCount, $productCount);
                                
                                // If callback returns false, stop processing
                                if ($callbackResult === false) {
                                    $this->logger->info("Callback returned false, stopping processing at product $productCount");
                                    break;
                                }
                            }
                            
                            $processedCount++;
                            $this->sessionProductCount++;
                            
                            $this->logger->info("Successfully parsed product: " . $product->getRecordReference() . 
                                            " ($processedCount of $productCount)");
                        }
                    } catch (\Exception $e) {
                        $this->logger->error("Error parsing product #$productCount: " . $e->getMessage());
                        
                        if (!$options['continue_on_error']) {
                            throw $e;
                        }
                    }
                }
            }
            
            // Create final checkpoint if enabled
            if ($this->checkpointsEnabled) {
                $this->createCheckpoint($productCount, $processedCount, $skippedCount, $headerProcessed, $versionDetected, true);
            }
            
            // Close the reader
            $reader->close();
            
            $this->logger->info("Streaming parse completed: $processedCount products processed, $skippedCount skipped");
            
            return $this->onix;
            
        } catch (\Exception $e) {
            $this->logger->error("Error parsing ONIX file: " . $e->getMessage());
            throw $e;
        } finally {
            // Restore previous error handling state
            libxml_use_internal_errors($previous);
            
            // Cleanup
            if ($this->positionTracker) {
                $this->positionTracker->close();
            }
        }
    }
    
    /**
     * Resume parsing from a checkpoint
     */
    private function resumeFromCheckpoint(string $xmlPath): Onix
    {
        $this->isResuming = true;
        
        // Load checkpoint
        $checkpointFile = $this->resumeOptions['resume_from_checkpoint'] ?: 
                         $this->checkpointManager->getCheckpointFileByPath($xmlPath);
        
        if (!$checkpointFile) {
            throw CheckpointException::loadFailed($xmlPath, "No checkpoint found for this file");
        }
        
        $this->currentResumePoint = $this->checkpointManager->loadCheckpoint($checkpointFile);
        
        // Validate checkpoint
        if (!$this->checkpointManager->validateCheckpoint($this->currentResumePoint)) {
            throw CheckpointException::validationFailed("Checkpoint validation failed");
        }
        
        $this->logger->info("Resuming from checkpoint at byte position: " . $this->currentResumePoint->getBytePosition());
        
        // Initialize position tracker and seek to resume position
        $this->positionTracker = new FilePositionTracker($xmlPath);
        $this->positionTracker->seekToPosition($this->currentResumePoint->getBytePosition());
        
        // Restore parser state
        $this->restoreParserState($this->currentResumePoint->getParserState());
        
        // Continue parsing from resume point
        return $this->continueParsingFromResumePoint($xmlPath);
    }
    
    /**
     * Continue parsing from resume point
     */
    private function continueParsingFromResumePoint(string $xmlPath): Onix
    {
        // Get resume options that might contain callback
        $options = $this->resumeOptions;
        
        $this->xmlPath = $xmlPath;
        
        if (!file_exists($xmlPath)) {
            throw new \Exception("XML file not found: $xmlPath");
        }
        
        // Apply restored state to Onix object
        $this->currentResumePoint->getParserState()->applyToOnix($this->onix);
        
        // Get restored parser state
        $parserState = $this->currentResumePoint->getParserState();
        $resumedProductCount = $parserState->getProcessedProductCount();
        $resumedProcessedCount = $parserState->getProcessedCount();
        $resumedSkippedCount = $parserState->getSkippedCount();
        
        $this->logger->info("Resuming parsing from product: $resumedProductCount (processed: $resumedProcessedCount, skipped: $resumedSkippedCount)");
        
        // Enable user error handling
        $previous = libxml_use_internal_errors(true);
        
        try {
            // Create XMLReader instance
            $reader = new \XMLReader();
            
            // Open the XML file
            if (!$reader->open($xmlPath)) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new \Exception('Failed to open XML file for resume: ' . $this->formatLibXMLErrors($errors));
            }
            
            // Variables to track progress (continue from checkpoint)
            $productCount = $resumedProductCount;
            $processedCount = $resumedProcessedCount;
            $skippedCount = $resumedSkippedCount;
            $headerProcessed = $parserState->isHeaderProcessed();
            $versionDetected = $parserState->isVersionDetected();
            
            // Seek to resume position using position tracker
            $this->positionTracker->seekToPosition($this->currentResumePoint->getBytePosition());
            
            // Continue reading from current position
            while ($reader->read()) {
                // Record position for important elements
                $this->positionTracker->synchronizeWithXmlReader($reader);
                
                // Process only element nodes
                if ($reader->nodeType !== \XMLReader::ELEMENT) {
                    continue;
                }
                
                // Check for namespace (might be needed if we're resuming before header)
                if (!$headerProcessed && $reader->namespaceURI) {
                    $this->hasNamespace = true;
                    $this->namespaceURI = $reader->namespaceURI;
                    $this->logger->info("XML has namespace: " . $this->namespaceURI);
                }
                
                // Detect ONIX version from root element (might be needed if resuming early)
                if (!$versionDetected && ($reader->name === 'ONIXMessage' || $reader->localName === 'ONIXMessage')) {
                    $release = $reader->getAttribute('release');
                    if ($release) {
                        $this->onix->setVersion('3.' . $release);
                    } else {
                        $this->onix->setVersion('3.0');
                    }
                    $versionDetected = true;
                }
                
                // Process header information (might be needed if resuming early)
                if (!$headerProcessed && 
                    ($reader->name === 'Header' || $reader->localName === 'Header')) {
                    
                    // Record position before header processing
                    $this->positionTracker->recordPosition('header_start');
                    
                    $headerNode = $this->getNodeFromReader($reader);
                    if ($headerNode) {
                        $header = $this->parseHeaderFromNode($headerNode);
                        $this->onix->setHeader($header);
                        $headerProcessed = true;
                    }
                }
                
                // Process product elements
                if ($reader->name === 'Product' || $reader->localName === 'Product') {
                    $productCount++;
                    
                    // Record position before product processing
                    $this->positionTracker->recordPosition('product_start', $productCount);
                    
                    // Skip products based on offset
                    if ($productCount <= ($options['offset'] ?? 0)) {
                        $skippedCount++;
                        $reader->next();
                        continue;
                    }
                    
                    // Check if we've reached the limit
                    if (($options['limit'] ?? 0) > 0 && $processedCount >= ($options['limit'] ?? 0)) {
                        break;
                    }
                    
                    // Create checkpoint if needed
                    if ($this->shouldCreateCheckpoint($productCount)) {
                        $this->createCheckpoint($productCount, $processedCount, $skippedCount, $headerProcessed, $versionDetected);
                    }
                    
                    try {
                        // Parse the product
                        $productNode = $this->getNodeFromReader($reader);
                        if ($productNode) {
                            $product = $this->parseProductStreaming($productNode);
                            $this->onix->setProduct($product);
                            
                            // Call callback if provided (CRITICAL: This enables callback execution in resume mode)
                            if (is_callable($options['callback'] ?? null)) {
                                $callbackResult = call_user_func($options['callback'], $product, $processedCount, $productCount);
                                
                                // If callback returns false, stop processing
                                if ($callbackResult === false) {
                                    $this->logger->info("Callback returned false, stopping resumed parsing at product $productCount");
                                    break;
                                }
                            }
                            
                            $processedCount++;
                            $this->sessionProductCount++;
                            
                            $this->logger->info("Successfully parsed product (resumed): " . $product->getRecordReference() . 
                                            " ($processedCount of $productCount)");
                        }
                    } catch (\Exception $e) {
                        $this->logger->error("Error parsing product #$productCount during resume: " . $e->getMessage());
                        
                        if (!($options['continue_on_error'] ?? true)) {
                            throw $e;
                        }
                    }
                }
            }
            
            // Create final checkpoint if enabled
            if ($this->checkpointsEnabled) {
                $this->createCheckpoint($productCount, $processedCount, $skippedCount, $headerProcessed, $versionDetected, true);
            }
            
            // Close the reader
            $reader->close();
            
            $this->logger->info("Resumed parsing completed: $processedCount products processed, $skippedCount skipped");
            
            return $this->onix;
            
        } catch (\Exception $e) {
            $this->logger->error("Error during resumed parsing: " . $e->getMessage());
            throw $e;
        } finally {
            // Restore previous error handling state
            libxml_use_internal_errors($previous);
            
            // Cleanup
            if ($this->positionTracker) {
                $this->positionTracker->close();
            }
        }
    }
    
    /**
     * Create checkpoint at current position
     */
    private function createCheckpoint(
        int $productCount,
        int $processedCount,
        int $skippedCount,
        bool $headerProcessed,
        bool $versionDetected,
        bool $isFinal = false
    ): void {
        try {
            // Create parser state
            $parserState = ParserState::fromParser(
                $this->hasNamespace,
                $this->namespaceURI,
                $headerProcessed,
                $versionDetected,
                $this->onix->getVersion(),
                $this->onix->getHeader(),
                $productCount,
                $processedCount,
                $skippedCount,
                $isFinal ? 'completed' : 'processing'
            );
            
            // Get current position and context
            $currentPosition = $this->positionTracker->getCurrentPosition();
            $xmlContext = $this->positionTracker->getXmlContext();
            
            // Create resume point
            $resumePoint = new ResumePoint(
                $currentPosition,
                $this->xmlPath,
                $this->calculateConsistentFileHash($this->xmlPath),
                filesize($this->xmlPath),
                $xmlContext,
                'Product', // Expected element at resume point
                $parserState
            );
            
            // Add metadata
            $resumePoint->addMetadata('session_products', $this->sessionProductCount);
            $resumePoint->addMetadata('is_final', $isFinal);
            
            // Save checkpoint
            $this->checkpointManager->saveCheckpoint($resumePoint, $this->currentCheckpointId);
            
            $this->lastCheckpointCount = $productCount;
            
            $this->logger->info("Checkpoint created at product $productCount (byte position: $currentPosition)");
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to create checkpoint: " . $e->getMessage());
            // Don't fail the parsing process for checkpoint errors
        }
    }
    
    /**
     * Calculate consistent file hash (first 8KB) matching ResumePoint validation method
     */
    private function calculateConsistentFileHash(string $filePath): string
    {
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception("Cannot open file for hashing: $filePath");
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
     * Restore parser state from checkpoint
     */
    private function restoreParserState(ParserState $state): void
    {
        $this->hasNamespace = $state->hasNamespace();
        $this->namespaceURI = $state->getNamespaceURI();
        $this->sessionProductCount = $state->getProcessedProductCount();
        $this->lastCheckpointCount = $state->getProcessedProductCount();
        
        // Restore Onix object state
        $this->onix = new Onix();
        $state->applyToOnix($this->onix);
        
        $this->logger->info("Parser state restored from checkpoint");
    }
    
    /**
     * Check if we should create a checkpoint
     */
    private function shouldCreateCheckpoint(int $productCount): bool
    {
        if (!$this->checkpointsEnabled) {
            return false;
        }
        
        return ($productCount - $this->lastCheckpointCount) >= $this->checkpointInterval;
    }
    
    /**
     * Check if we should resume from a checkpoint
     */
    private function shouldResumeFromCheckpoint(string $xmlPath): bool
    {
        if (!$this->resumeOptions['auto_resume'] && !$this->resumeOptions['resume_from_checkpoint']) {
            return false;
        }
        
        return $this->checkpointManager->getCheckpointFileByPath($xmlPath) !== null;
    }
    
    /**
     * Generate checkpoint ID based on file path
     */
    private function generateCheckpointId(string $xmlPath): string
    {
        return md5($xmlPath . '|' . filemtime($xmlPath));
    }
    
    /**
     * Get current parsing statistics
     */
    public function getStats(): array
    {
        $stats = [
            'session_products' => $this->sessionProductCount,
            'last_checkpoint_count' => $this->lastCheckpointCount,
            'checkpoints_enabled' => $this->checkpointsEnabled,
            'checkpoint_interval' => $this->checkpointInterval,
            'is_resuming' => $this->isResuming,
        ];
        
        if ($this->positionTracker) {
            $stats['position_tracker'] = $this->positionTracker->getStats();
        }
        
        if ($this->currentResumePoint) {
            $stats['resume_point'] = [
                'byte_position' => $this->currentResumePoint->getBytePosition(),
                'created_at' => $this->currentResumePoint->getCreatedAt(),
            ];
        }
        
        return $stats;
    }
    
    /**
     * Clean up old checkpoints
     */
    public function cleanupCheckpoints(): int
    {
        return $this->checkpointManager->cleanupOldCheckpoints();
    }
    
    /**
     * Get checkpoint manager
     */
    public function getCheckpointManager(): CheckpointManager
    {
        return $this->checkpointManager;
    }
    
    /**
     * Set checkpoint manager
     */
    public function setCheckpointManager(CheckpointManager $manager): void
    {
        $this->checkpointManager = $manager;
    }
}