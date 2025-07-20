<?php

namespace ONIXParser;

use ONIXParser\Model\Onix;
use ONIXParser\Resume\CheckpointManager;
use ONIXParser\Resume\ParserState;
use ONIXParser\Resume\ResumePoint;
use ONIXParser\Exception\CheckpointException;

/**
 * Improved Resumable ONIX Parser using Chunk-based Processing
 * 
 * Drop-in replacement for ResumableOnixParser with better performance
 * Uses chunk-based parsing to solve file restart and memory issues
 */
class ImprovedResumableOnixParser extends OnixParser
{
    /** @var ChunkOnixParser */
    private $chunkParser;
    
    /** @var CheckpointManager */
    private $checkpointManager;
    
    /** @var bool */
    private $checkpointsEnabled = false;
    
    /** @var int */
    private $checkpointInterval = 100;
    
    /** @var string */
    private $currentCheckpointId;
    
    /** @var int */
    private $sessionProductCount = 0;
    
    /** @var bool */
    private $isResuming = false;
    
    public function __construct(Logger $logger = null, CheckpointManager $checkpointManager = null)
    {
        parent::__construct($logger);
        $this->checkpointManager = $checkpointManager ?: new CheckpointManager(null, $this->logger);
    }
    
    /**
     * Enhanced streaming parser with chunk-based resume capability
     * Compatible with existing ResumableOnixParser interface
     */
    public function parseFileStreaming(string $xmlPath, array $options = []): Onix
    {
        $this->xmlPath = $xmlPath;
        
        // Initialize chunk parser
        $this->chunkParser = new ChunkOnixParser($xmlPath, $this->logger);
        
        // Merge with resume-specific options
        $options = array_merge([
            'limit' => 0,
            'offset' => 0,
            'callback' => null,
            'continue_on_error' => true,
            'enable_checkpoints' => false,
            'checkpoint_interval' => 100,
            'checkpoint_id' => null,
            'resume_from_checkpoint' => null,
            'auto_resume' => true,
            'chunk_size' => 512 * 1024, // 512KB default
        ], $options);
        
        $this->checkpointsEnabled = $options['enable_checkpoints'];
        $this->checkpointInterval = $options['checkpoint_interval'];
        $this->currentCheckpointId = $options['checkpoint_id'] ?: $this->generateCheckpointId($xmlPath);
        
        // Set chunk size if specified
        if (isset($options['chunk_size'])) {
            $this->chunkParser->setChunkSize($options['chunk_size']);
        }
        
        // Check for existing checkpoint or explicit resume
        if ($this->shouldResumeFromCheckpoint($options)) {
            return $this->resumeFromCheckpoint($xmlPath, $options);
        }
        
        // Start fresh parsing with chunk-based processing
        return $this->parseWithChunks($xmlPath, $options);
    }
    
    /**
     * Parse using chunk-based processing
     */
    private function parseWithChunks(string $xmlPath, array $options): Onix
    {
        $this->onix = new Onix();
        
        $offset = $options['offset'];
        $limit = $options['limit'];
        $callback = $options['callback'];
        $continueOnError = $options['continue_on_error'];
        
        $this->logger->info("Starting improved resumable parsing with chunk processing");
        $this->logger->info("Offset: $offset, Limit: $limit, Checkpoints: " . ($this->checkpointsEnabled ? 'enabled' : 'disabled'));
        
        $processedCount = 0;
        $totalCount = 0;
        
        // Use chunk parser with our callback wrapper
        $chunkCallback = function($productXml, $productNumber, $bytePosition = null) use ($callback, &$processedCount, &$totalCount, $offset, $limit, $continueOnError) {
            $totalCount = $productNumber;
            
            // Skip products before offset
            if ($productNumber <= $offset) {
                return null;
            }
            
            // Stop if limit reached
            if ($limit > 0 && $processedCount >= $limit) {
                return false; // Signal to stop
            }
            
            try {
                // Parse the product XML to Product object
                $product = $this->parseProductXml($productXml);
                if ($product) {
                    $this->onix->setProduct($product);
                    
                    // Call user callback if provided
                    if (is_callable($callback)) {
                        $callbackResult = call_user_func($callback, $product, $processedCount, $productNumber);
                        
                        if ($callbackResult === false) {
                            $this->logger->info("Callback returned false, stopping at product $productNumber");
                            return false;
                        }
                    }
                    
                    $processedCount++;
                    $this->sessionProductCount++;
                    
                    // Create checkpoint if needed
                    if ($this->shouldCreateCheckpoint($productNumber)) {
                        $this->createChunkCheckpoint($productNumber, $processedCount, $bytePosition);
                    }
                    
                    return $product;
                }
                
            } catch (\Exception $e) {
                $this->logger->error("Error parsing product #$productNumber: " . $e->getMessage());
                
                if (!$continueOnError) {
                    throw $e;
                }
            }
            
            return null;
        };
        
        // Use parseWithLimits for better control
        if ($offset > 0 || $limit > 0) {
            $this->chunkParser->parseWithLimits($chunkCallback, $offset, $limit);
        } else {
            // Use checkpointing version for full file processing
            $this->chunkParser->parseWithCheckpoints($chunkCallback, $this->checkpointInterval);
        }
        
        $this->logger->info("Chunk-based parsing completed: $processedCount products processed");
        
        return $this->onix;
    }
    
    /**
     * Parse individual product XML fragment to Product object
     */
    private function parseProductXml(string $productXml): ?\ONIXParser\Model\Product
    {
        // Detect namespace
        $hasNamespace = strpos($productXml, 'xmlns') !== false || strpos($productXml, ':Product') !== false;
        
        // Wrap product XML in a complete document for parsing
        $wrappedXml = '<?xml version="1.0" encoding="UTF-8"?>';
        
        if ($hasNamespace) {
            // Extract namespace from the product tag or use default
            if (preg_match('/xmlns:?(\w*)="([^"]*onix[^"]*)"/', $productXml, $matches)) {
                $nsPrefix = $matches[1];
                $nsUri = $matches[2];
                $wrappedXml .= "<ONIXMessage xmlns{$nsPrefix}=\"{$nsUri}\">";
            } else {
                $wrappedXml .= '<ONIXMessage xmlns="http://www.editeur.org/onix/3.0/reference">';
            }
        } else {
            $wrappedXml .= '<ONIXMessage>';
        }
        
        $wrappedXml .= $productXml . '</ONIXMessage>';
        
        // Parse with DOMDocument
        $dom = new \DOMDocument();
        $loaded = $dom->loadXML($wrappedXml);
        
        if (!$loaded) {
            $this->logger->warning("Failed to parse product XML");
            return null;
        }
        
        $xpath = new \DOMXPath($dom);
        if ($hasNamespace) {
            $xpath->registerNamespace('onix', 'http://www.editeur.org/onix/3.0/reference');
        }
        
        // Find the product element
        $productNodes = $hasNamespace ? 
            $xpath->query('//onix:Product') : 
            $xpath->query('//Product');
        
        if ($productNodes->length === 0) {
            return null;
        }
        
        $productNode = $productNodes->item(0);
        return $this->parseProductFromNode($productNode, $xpath);
    }
    
    /**
     * Create checkpoint with chunk-based position information
     */
    private function createChunkCheckpoint(int $productNumber, int $processedCount, ?int $bytePosition): void
    {
        if (!$this->checkpointsEnabled) {
            return;
        }
        
        try {
            // Create parser state
            $parserState = ParserState::fromParser(
                $this->hasNamespace,
                $this->namespaceURI,
                true, // header processed
                true, // version detected
                $this->onix->getVersion() ?: '3.0',
                $this->onix->getHeader(),
                $productNumber,
                $processedCount,
                0, // skipped count
                'processing'
            );
            
            // Create resume point with chunk position
            $resumePoint = new ResumePoint(
                $bytePosition ?: 0,
                $this->xmlPath,
                $this->calculateFileHash($this->xmlPath),
                filesize($this->xmlPath),
                ['chunk_based' => true, 'product_number' => $productNumber],
                'Product',
                $parserState
            );
            
            // Add metadata
            $resumePoint->addMetadata('session_products', $this->sessionProductCount);
            $resumePoint->addMetadata('chunk_based', true);
            $resumePoint->addMetadata('product_number', $productNumber);
            
            // Save checkpoint
            $this->checkpointManager->saveCheckpoint($resumePoint, $this->currentCheckpointId);
            
            $this->logger->info("Chunk checkpoint created at product $productNumber" . 
                               ($bytePosition ? " (byte position: " . number_format($bytePosition) . ")" : ""));
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to create chunk checkpoint: " . $e->getMessage());
        }
    }
    
    /**
     * Resume from checkpoint using chunk parser
     */
    private function resumeFromCheckpoint(string $xmlPath, array $options): Onix
    {
        $this->isResuming = true;
        
        $checkpointFile = $options['resume_from_checkpoint'] ?: 
                         $this->checkpointManager->getCheckpointFileByPath($xmlPath);
        
        if (!$checkpointFile) {
            throw CheckpointException::loadFailed($xmlPath, "No checkpoint found for this file");
        }
        
        $resumePoint = $this->checkpointManager->loadCheckpoint($checkpointFile);
        $this->logger->info("Resuming from chunk checkpoint at byte position: " . number_format($resumePoint->getBytePosition()));
        
        // Restore parser state
        $parserState = $resumePoint->getParserState();
        $this->hasNamespace = $parserState->hasNamespace();
        $this->namespaceURI = $parserState->getNamespaceURI();
        
        // Create new Onix object and restore state
        $this->onix = new Onix();
        $parserState->applyToOnix($this->onix);
        
        // Get product number from metadata
        $resumeProductNumber = $resumePoint->getMetadata('product_number') ?: $parserState->getTotalProductCount();
        
        // Adjust offset to resume from correct position
        $options['offset'] = $resumeProductNumber;
        
        $this->logger->info("Resuming from product $resumeProductNumber");
        
        // Continue parsing from resume point
        return $this->parseWithChunks($xmlPath, $options);
    }
    
    /**
     * Check if checkpoint should be created
     */
    private function shouldCreateCheckpoint(int $productNumber): bool
    {
        if (!$this->checkpointsEnabled) {
            return false;
        }
        
        return ($productNumber % $this->checkpointInterval) === 0;
    }
    
    /**
     * Check if should resume from checkpoint
     */
    private function shouldResumeFromCheckpoint(array $options): bool
    {
        return $options['auto_resume'] || $options['resume_from_checkpoint'];
    }
    
    /**
     * Generate checkpoint ID
     */
    private function generateCheckpointId(string $xmlPath): string
    {
        return md5($xmlPath . '|' . filemtime($xmlPath));
    }
    
    /**
     * Calculate file hash
     */
    private function calculateFileHash(string $filePath): string
    {
        return md5_file($filePath);
    }
    
    /**
     * Get parsing statistics (compatible with ResumableOnixParser)
     */
    public function getStats(): array
    {
        $stats = [
            'session_products' => $this->sessionProductCount,
            'checkpoints_enabled' => $this->checkpointsEnabled,
            'checkpoint_interval' => $this->checkpointInterval,
            'is_resuming' => $this->isResuming,
            'parser_type' => 'chunk_based'
        ];
        
        if ($this->chunkParser) {
            $stats['chunk_parser_stats'] = $this->chunkParser->getStats();
        }
        
        return $stats;
    }
    
    /**
     * Clean up old checkpoints (compatible with ResumableOnixParser)
     */
    public function cleanupCheckpoints(): int
    {
        $cleaned = $this->checkpointManager->cleanupOldCheckpoints();
        
        // Also clean up chunk parser checkpoints
        if ($this->chunkParser) {
            $this->chunkParser->clearCheckpoint();
        }
        
        return $cleaned;
    }
    
    /**
     * Get checkpoint manager (compatible with ResumableOnixParser)
     */
    public function getCheckpointManager(): CheckpointManager
    {
        return $this->checkpointManager;
    }
    
    /**
     * Set checkpoint manager (compatible with ResumableOnixParser)
     */
    public function setCheckpointManager(CheckpointManager $manager): void
    {
        $this->checkpointManager = $manager;
    }
    
    /**
     * Get the underlying chunk parser
     */
    public function getChunkParser(): ?ChunkOnixParser
    {
        return $this->chunkParser;
    }
}