<?php

namespace ONIXParser;

/**
 * Product Boundary Scanner
 * 
 * Scans ONIX XML files to find exact byte positions of <Product> elements
 * Provides true resumable parsing by maintaining exact file positions
 */
class ProductBoundaryScanner
{
    /** @var string */
    private $filePath;
    
    /** @var resource */
    private $fileHandle;
    
    /** @var int */
    private $fileSize;
    
    /** @var array */
    private $productBoundaries = [];
    
    /** @var Logger */
    private $logger;
    
    /** @var int */
    private $chunkSize = 8192; // 8KB chunks for efficient reading
    
    /** @var string */
    private $buffer = '';
    
    /** @var int */
    private $bufferPosition = 0;
    
    public function __construct(string $filePath, Logger $logger = null)
    {
        $this->filePath = $filePath;
        $this->logger = $logger ?: new Logger();
        $this->fileSize = filesize($filePath);
        
        if (!file_exists($filePath)) {
            throw new \Exception("File not found: $filePath");
        }
        
        $this->openFile();
    }
    
    /**
     * Open file for reading
     */
    private function openFile(): void
    {
        $this->fileHandle = fopen($this->filePath, 'r');
        if (!$this->fileHandle) {
            throw new \Exception("Cannot open file: {$this->filePath}");
        }
    }
    
    /**
     * Scan file to find all product boundaries
     * Returns array of [start_position, end_position, product_number]
     */
    public function scanProductBoundaries(): array
    {
        $this->logger->info("Starting product boundary scan for file: " . basename($this->filePath));
        
        $productCount = 0;
        $this->productBoundaries = [];
        $currentPosition = 0;
        $this->buffer = '';
        $this->bufferPosition = 0;
        
        // Reset file position
        fseek($this->fileHandle, 0);
        
        // Look for namespace information first
        $namespacePrefix = $this->detectNamespacePrefix();
        $productStartTag = $namespacePrefix ? "<{$namespacePrefix}:Product" : "<Product";
        $productEndTag = $namespacePrefix ? "</{$namespacePrefix}:Product>" : "</Product>";
        
        $this->logger->info("Scanning for product tags: '$productStartTag' and '$productEndTag'");
        
        while (!feof($this->fileHandle)) {
            // Read chunk and add to buffer
            $chunk = fread($this->fileHandle, $this->chunkSize);
            if ($chunk === false) {
                break;
            }
            
            $this->buffer .= $chunk;
            $currentFilePosition = ftell($this->fileHandle);
            
            // Scan buffer for product start tags
            $searchStart = 0;
            while (($startPos = strpos($this->buffer, $productStartTag, $searchStart)) !== false) {
                $productCount++;
                $absoluteStartPos = $this->bufferPosition + $startPos;
                
                // Find the end of this product
                $endTagPos = strpos($this->buffer, $productEndTag, $startPos);
                
                if ($endTagPos !== false) {
                    // Found complete product in buffer
                    $absoluteEndPos = $this->bufferPosition + $endTagPos + strlen($productEndTag);
                    
                    $this->productBoundaries[] = [
                        'start_position' => $absoluteStartPos,
                        'end_position' => $absoluteEndPos,
                        'product_number' => $productCount,
                        'length' => $absoluteEndPos - $absoluteStartPos
                    ];
                    
                    if ($productCount % 1000 === 0) {
                        $this->logger->info("Scanned $productCount products (current position: " . number_format($absoluteEndPos) . " bytes)");
                    }
                    
                    $searchStart = $endTagPos + strlen($productEndTag);
                } else {
                    // Product spans beyond current buffer - need more data
                    // Keep reading until we find the end tag
                    $productStart = $absoluteStartPos;
                    $searchBuffer = $this->buffer . $this->readUntilProductEnd($productEndTag, $currentFilePosition);
                    
                    $relativeEndPos = strpos($searchBuffer, $productEndTag, $startPos);
                    if ($relativeEndPos !== false) {
                        $absoluteEndPos = $this->bufferPosition + $relativeEndPos + strlen($productEndTag);
                        
                        $this->productBoundaries[] = [
                            'start_position' => $absoluteStartPos,
                            'end_position' => $absoluteEndPos,
                            'product_number' => $productCount,
                            'length' => $absoluteEndPos - $absoluteStartPos
                        ];
                        
                        if ($productCount % 1000 === 0) {
                            $this->logger->info("Scanned $productCount products (current position: " . number_format($absoluteEndPos) . " bytes)");
                        }
                    }
                    
                    break; // Exit inner loop to read more data
                }
            }
            
            // Keep only the last part of buffer to catch products spanning chunks
            if (strlen($this->buffer) > $this->chunkSize * 2) {
                $keepSize = $this->chunkSize;
                $discardSize = strlen($this->buffer) - $keepSize;
                $this->buffer = substr($this->buffer, $discardSize);
                $this->bufferPosition += $discardSize;
            }
        }
        
        $this->logger->info("Product boundary scan complete: $productCount products found");
        $this->logger->info("File size: " . number_format($this->fileSize) . " bytes");
        
        return $this->productBoundaries;
    }
    
    /**
     * Read additional data until product end tag is found
     */
    private function readUntilProductEnd(string $endTag, int $startPosition): string
    {
        $additionalData = '';
        $maxReadSize = 1024 * 1024; // 1MB max for a single product
        $readSize = 0;
        
        while (!feof($this->fileHandle) && $readSize < $maxReadSize) {
            $chunk = fread($this->fileHandle, $this->chunkSize);
            if ($chunk === false) {
                break;
            }
            
            $additionalData .= $chunk;
            $readSize += strlen($chunk);
            
            if (strpos($additionalData, $endTag) !== false) {
                break;
            }
        }
        
        return $additionalData;
    }
    
    /**
     * Detect namespace prefix in XML
     */
    private function detectNamespacePrefix(): ?string
    {
        fseek($this->fileHandle, 0);
        $headerChunk = fread($this->fileHandle, 4096); // Read first 4KB
        
        // Look for namespace declarations
        if (preg_match('/xmlns:(\w+)="[^"]*onix/', $headerChunk, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Get product XML content by position
     */
    public function getProductXml(int $startPosition, int $endPosition): string
    {
        $length = $endPosition - $startPosition;
        
        fseek($this->fileHandle, $startPosition);
        $productXml = fread($this->fileHandle, $length);
        
        if ($productXml === false) {
            throw new \Exception("Failed to read product XML at position $startPosition");
        }
        
        return $productXml;
    }
    
    /**
     * Get product boundary by product number (1-indexed)
     */
    public function getProductBoundary(int $productNumber): ?array
    {
        foreach ($this->productBoundaries as $boundary) {
            if ($boundary['product_number'] === $productNumber) {
                return $boundary;
            }
        }
        
        return null;
    }
    
    /**
     * Get all products within a range
     */
    public function getProductRange(int $startProduct, int $endProduct): array
    {
        $range = [];
        
        foreach ($this->productBoundaries as $boundary) {
            if ($boundary['product_number'] >= $startProduct && $boundary['product_number'] <= $endProduct) {
                $range[] = $boundary;
            }
        }
        
        return $range;
    }
    
    /**
     * Get file statistics
     */
    public function getStats(): array
    {
        return [
            'file_path' => $this->filePath,
            'file_size' => $this->fileSize,
            'product_count' => count($this->productBoundaries),
            'avg_product_size' => count($this->productBoundaries) > 0 ? 
                round($this->fileSize / count($this->productBoundaries)) : 0,
            'boundaries_scanned' => !empty($this->productBoundaries)
        ];
    }
    
    /**
     * Close file handle
     */
    public function close(): void
    {
        if ($this->fileHandle) {
            fclose($this->fileHandle);
            $this->fileHandle = null;
        }
    }
    
    public function __destruct()
    {
        $this->close();
    }
}