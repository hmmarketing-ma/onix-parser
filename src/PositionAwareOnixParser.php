<?php

namespace ONIXParser;

use ONIXParser\Model\Onix;
use ONIXParser\Model\Product;
use ONIXParser\Resume\CheckpointManager;
use ONIXParser\Resume\ParserState;
use ONIXParser\Resume\ResumePoint;
use ONIXParser\Exception\CheckpointException;

/**
 * Position-Aware ONIX Parser
 * 
 * Uses ProductBoundaryScanner for exact byte position tracking
 * Provides true resumable parsing without XMLReader limitations
 */
class PositionAwareOnixParser extends OnixParser
{
    /** @var ProductBoundaryScanner */
    private $boundaryScanner;
    
    /** @var CheckpointManager */
    private $checkpointManager;
    
    /** @var array */
    private $productBoundaries = [];
    
    /** @var bool */
    private $boundariesScanned = false;
    
    /** @var bool */
    private $checkpointsEnabled = false;
    
    /** @var int */
    private $checkpointInterval = 100;
    
    /** @var string */
    private $currentCheckpointId;
    
    /** @var int */
    private $sessionProductCount = 0;
    
    public function __construct(Logger $logger = null, CheckpointManager $checkpointManager = null)
    {
        parent::__construct($logger);
        $this->checkpointManager = $checkpointManager ?: new CheckpointManager(null, $this->logger);
    }
    
    /**
     * Parse file with exact position tracking and resumable capabilities
     */
    public function parseFileWithPositions(string $xmlPath, array $options = []): Onix
    {
        $this->xmlPath = $xmlPath;
        
        // Initialize boundary scanner
        $this->boundaryScanner = new ProductBoundaryScanner($xmlPath, $this->logger);
        
        // Merge options
        $options = array_merge([
            'offset' => 0,
            'limit' => 0,
            'callback' => null,
            'continue_on_error' => true,
            'enable_checkpoints' => false,
            'checkpoint_interval' => 100,
            'checkpoint_id' => null,
            'resume_from_checkpoint' => null,
            'auto_resume' => false,
        ], $options);
        
        $this->checkpointsEnabled = $options['enable_checkpoints'];
        $this->checkpointInterval = $options['checkpoint_interval'];
        $this->currentCheckpointId = $options['checkpoint_id'] ?: $this->generateCheckpointId($xmlPath);
        
        // Check for resume
        if ($this->shouldResumeFromCheckpoint($options)) {
            return $this->resumeFromCheckpoint($xmlPath, $options);
        }
        
        // Scan product boundaries if not already done
        if (!$this->boundariesScanned) {
            $this->logger->info("Scanning product boundaries for exact position tracking");
            $this->productBoundaries = $this->boundaryScanner->scanProductBoundaries();
            $this->boundariesScanned = true;
        }
        
        return $this->parseWithBoundaries($xmlPath, $options);
    }
    
    /**
     * Parse using pre-scanned product boundaries
     */
    private function parseWithBoundaries(string $xmlPath, array $options): Onix
    {
        $this->onix = new Onix();
        
        $offset = $options['offset'];
        $limit = $options['limit'];
        $callback = $options['callback'];
        $continueOnError = $options['continue_on_error'];
        
        $processedCount = 0;
        $skippedCount = 0;
        $totalProducts = count($this->productBoundaries);
        
        $this->logger->info("Starting position-aware parsing: $totalProducts products found");
        $this->logger->info("Processing from offset $offset" . ($limit > 0 ? " with limit $limit" : ""));
        
        foreach ($this->productBoundaries as $boundary) {
            $productNumber = $boundary['product_number'];
            
            // Skip products based on offset
            if ($productNumber <= $offset) {
                $skippedCount++;
                continue;
            }
            
            // Check limit
            if ($limit > 0 && $processedCount >= $limit) {
                $this->logger->info("Reached processing limit of $limit products");
                break;
            }
            
            // Create checkpoint if needed
            if ($this->shouldCreateCheckpoint($productNumber)) {
                $this->createPositionCheckpoint($boundary, $processedCount, $skippedCount);
            }
            
            try {
                // Extract and parse individual product XML
                $productXml = $this->boundaryScanner->getProductXml(
                    $boundary['start_position'], 
                    $boundary['end_position']
                );
                
                $product = $this->parseProductXml($productXml);
                if ($product) {
                    $this->onix->setProduct($product);
                    
                    // Call callback if provided
                    if (is_callable($callback)) {
                        $callbackResult = call_user_func($callback, $product, $processedCount, $productNumber);
                        
                        if ($callbackResult === false) {
                            $this->logger->info("Callback returned false, stopping at product $productNumber");
                            break;
                        }
                    }
                    
                    $processedCount++;
                    $this->sessionProductCount++;
                    
                    if ($productNumber % 100 === 0) {
                        $this->logger->info("Processed product $productNumber (position: " . 
                                          number_format($boundary['start_position']) . " bytes)");
                    }
                }
                
            } catch (\Exception $e) {
                $this->logger->error("Error parsing product #$productNumber at position {$boundary['start_position']}: " . $e->getMessage());
                
                if (!$continueOnError) {
                    throw $e;
                }
            }
        }
        
        // Create final checkpoint
        if ($this->checkpointsEnabled) {
            $lastBoundary = end($this->productBoundaries);
            if ($lastBoundary) {
                $this->createPositionCheckpoint($lastBoundary, $processedCount, $skippedCount, true);
            }
        }
        
        $this->logger->info("Position-aware parsing completed: $processedCount products processed, $skippedCount skipped");
        
        return $this->onix;
    }
    
    /**
     * Parse individual product XML fragment
     */
    private function parseProductXml(string $productXml): ?Product
    {
        // Wrap product XML in a complete document for parsing
        $wrappedXml = '<?xml version="1.0" encoding="UTF-8"?>';
        
        // Add namespace if detected
        if ($this->hasNamespace) {
            $wrappedXml .= '<ONIXMessage xmlns="' . $this->namespaceURI . '">';
        } else {
            $wrappedXml .= '<ONIXMessage>';
        }
        
        $wrappedXml .= $productXml . '</ONIXMessage>';
        
        // Parse with DOMDocument for exact control
        $dom = new \DOMDocument();
        $dom->loadXML($wrappedXml);
        
        $xpath = new \DOMXPath($dom);
        if ($this->hasNamespace) {
            $xpath->registerNamespace('onix', $this->namespaceURI);
        }
        
        // Find the product element
        $productNodes = $this->hasNamespace ? 
            $xpath->query('//onix:Product') : 
            $xpath->query('//Product');
        
        if ($productNodes->length === 0) {
            return null;
        }
        
        $productNode = $productNodes->item(0);
        return $this->parseProductFromNode($productNode, $xpath);
    }
    
    /**
     * Create checkpoint with exact position information
     */
    private function createPositionCheckpoint(array $boundary, int $processedCount, int $skippedCount, bool $isFinal = false): void
    {
        try {
            $productNumber = $boundary['product_number'];
            $bytePosition = $boundary['start_position'];
            
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
                $skippedCount,
                $isFinal ? 'completed' : 'processing'
            );
            
            // Create resume point with EXACT position
            $resumePoint = new ResumePoint(
                $bytePosition,
                $this->xmlPath,
                $this->calculateFileHash($this->xmlPath),
                filesize($this->xmlPath),
                ['product_boundary' => $boundary],
                'Product',
                $parserState
            );
            
            // Add metadata
            $resumePoint->addMetadata('session_products', $this->sessionProductCount);
            $resumePoint->addMetadata('is_final', $isFinal);
            $resumePoint->addMetadata('exact_position', true);
            $resumePoint->addMetadata('boundary_info', $boundary);
            
            // Save checkpoint
            $this->checkpointManager->saveCheckpoint($resumePoint, $this->currentCheckpointId);
            
            $this->logger->info("Position checkpoint created at product $productNumber (byte position: " . number_format($bytePosition) . ")");
            
        } catch (\Exception $e) {
            $this->logger->error("Failed to create position checkpoint: " . $e->getMessage());
        }
    }
    
    /**
     * Resume from checkpoint with exact positioning
     */
    private function resumeFromCheckpoint(string $xmlPath, array $options): Onix
    {
        $checkpointFile = $options['resume_from_checkpoint'] ?: 
                         $this->checkpointManager->getCheckpointFileByPath($xmlPath);
        
        if (!$checkpointFile) {
            throw CheckpointException::loadFailed($xmlPath, "No checkpoint found");
        }
        
        $resumePoint = $this->checkpointManager->loadCheckpoint($checkpointFile);
        $this->logger->info("Resuming from exact position: " . number_format($resumePoint->getBytePosition()) . " bytes");
        
        // Restore parser state
        $parserState = $resumePoint->getParserState();
        $this->hasNamespace = $parserState->hasNamespace();
        $this->namespaceURI = $parserState->getNamespaceURI();
        
        // Create new Onix object and restore state
        $this->onix = new Onix();
        $parserState->applyToOnix($this->onix);
        
        // Get boundary information from metadata
        $boundaryInfo = $resumePoint->getMetadata('boundary_info');
        if ($boundaryInfo) {
            $resumeProductNumber = $boundaryInfo['product_number'];
            $this->logger->info("Resuming from product $resumeProductNumber");
            
            // Adjust offset to resume from correct position
            $options['offset'] = $resumeProductNumber - 1;
        }
        
        // Continue parsing from resume point
        return $this->parseWithBoundaries($xmlPath, $options);
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
     * Get parsing statistics
     */
    public function getPositionStats(): array
    {
        $stats = [
            'session_products' => $this->sessionProductCount,
            'checkpoints_enabled' => $this->checkpointsEnabled,
            'checkpoint_interval' => $this->checkpointInterval,
            'boundaries_scanned' => $this->boundariesScanned,
            'total_boundaries' => count($this->productBoundaries)
        ];
        
        if ($this->boundaryScanner) {
            $stats['scanner_stats'] = $this->boundaryScanner->getStats();
        }
        
        return $stats;
    }
    
    /**
     * Get boundary scanner for external use
     */
    public function getBoundaryScanner(): ?ProductBoundaryScanner
    {
        return $this->boundaryScanner;
    }
    
    /**
     * Cleanup
     */
    public function close(): void
    {
        if ($this->boundaryScanner) {
            $this->boundaryScanner->close();
        }
    }
    
    public function __destruct()
    {
        $this->close();
    }
}