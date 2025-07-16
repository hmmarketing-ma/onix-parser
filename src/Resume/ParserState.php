<?php

namespace ONIXParser\Resume;

use ONIXParser\Exception\CheckpointException;
use ONIXParser\Model\Header;
use ONIXParser\Model\Onix;

/**
 * Represents the complete state of the ONIX parser at a specific point in time
 * This includes all necessary context to resume parsing from that point
 */
class ParserState
{
    /** @var bool Whether the XML document has a namespace */
    private $hasNamespace;
    
    /** @var string The namespace URI if present */
    private $namespaceURI;
    
    /** @var bool Whether the header has been processed */
    private $headerProcessed;
    
    /** @var bool Whether the ONIX version has been detected */
    private $versionDetected;
    
    /** @var string The detected ONIX version */
    private $onixVersion;
    
    /** @var array Serialized header data */
    private $headerData;
    
    /** @var int Total number of products found so far */
    private $totalProductCount;
    
    /** @var int Number of products successfully processed */
    private $processedProductCount;
    
    /** @var int Number of products skipped */
    private $skippedProductCount;
    
    /** @var string Current parsing phase */
    private $parsingPhase;
    
    /** @var array Additional context data */
    private $contextData;
    
    /** @var int Timestamp when state was captured */
    private $timestamp;
    
    public function __construct()
    {
        $this->hasNamespace = false;
        $this->namespaceURI = '';
        $this->headerProcessed = false;
        $this->versionDetected = false;
        $this->onixVersion = '';
        $this->headerData = [];
        $this->totalProductCount = 0;
        $this->processedProductCount = 0;
        $this->skippedProductCount = 0;
        $this->parsingPhase = 'init';
        $this->contextData = [];
        $this->timestamp = time();
    }
    
    /**
     * Create state from current parser context
     */
    public static function fromParser(
        bool $hasNamespace,
        string $namespaceURI,
        bool $headerProcessed,
        bool $versionDetected,
        string $onixVersion,
        ?Header $header,
        int $totalProductCount,
        int $processedProductCount,
        int $skippedProductCount,
        string $parsingPhase = 'processing'
    ): self {
        $state = new self();
        $state->hasNamespace = $hasNamespace;
        $state->namespaceURI = $namespaceURI;
        $state->headerProcessed = $headerProcessed;
        $state->versionDetected = $versionDetected;
        $state->onixVersion = $onixVersion;
        $state->totalProductCount = $totalProductCount;
        $state->processedProductCount = $processedProductCount;
        $state->skippedProductCount = $skippedProductCount;
        $state->parsingPhase = $parsingPhase;
        
        // Serialize header data if available
        if ($header) {
            $state->headerData = [
                'sender' => $header->getSender(),
                'contact' => $header->getContact(),
                'email' => $header->getEmail(),
                'sent_date_time' => $header->getSentDateTime(),
            ];
        }
        
        return $state;
    }
    
    /**
     * Serialize state to array for JSON storage
     */
    public function toArray(): array
    {
        return [
            'has_namespace' => $this->hasNamespace,
            'namespace_uri' => $this->namespaceURI,
            'header_processed' => $this->headerProcessed,
            'version_detected' => $this->versionDetected,
            'onix_version' => $this->onixVersion,
            'header_data' => $this->headerData,
            'total_product_count' => $this->totalProductCount,
            'processed_product_count' => $this->processedProductCount,
            'skipped_product_count' => $this->skippedProductCount,
            'parsing_phase' => $this->parsingPhase,
            'context_data' => $this->contextData,
            'timestamp' => $this->timestamp,
        ];
    }
    
    /**
     * Create state from serialized array
     */
    public static function fromArray(array $data): self
    {
        $state = new self();
        
        // Validate required fields
        $requiredFields = [
            'has_namespace', 'namespace_uri', 'header_processed', 'version_detected',
            'onix_version', 'total_product_count', 'processed_product_count',
            'skipped_product_count', 'parsing_phase', 'timestamp'
        ];
        
        foreach ($requiredFields as $field) {
            if (!array_key_exists($field, $data)) {
                throw CheckpointException::corrupted("Missing required field: {$field}");
            }
        }
        
        $state->hasNamespace = (bool)$data['has_namespace'];
        $state->namespaceURI = (string)$data['namespace_uri'];
        $state->headerProcessed = (bool)$data['header_processed'];
        $state->versionDetected = (bool)$data['version_detected'];
        $state->onixVersion = (string)$data['onix_version'];
        $state->headerData = $data['header_data'] ?? [];
        $state->totalProductCount = (int)$data['total_product_count'];
        $state->processedProductCount = (int)$data['processed_product_count'];
        $state->skippedProductCount = (int)$data['skipped_product_count'];
        $state->parsingPhase = (string)$data['parsing_phase'];
        $state->contextData = $data['context_data'] ?? [];
        $state->timestamp = (int)$data['timestamp'];
        
        return $state;
    }
    
    /**
     * Apply this state to an Onix object
     */
    public function applyToOnix(Onix $onix): void
    {
        if ($this->versionDetected) {
            $onix->setVersion($this->onixVersion);
        }
        
        if ($this->headerProcessed && !empty($this->headerData)) {
            $header = new Header();
            $header->setSender($this->headerData['sender'] ?? '');
            $header->setContact($this->headerData['contact'] ?? '');
            $header->setEmail($this->headerData['email'] ?? '');
            $header->setSentDateTime($this->headerData['sent_date_time'] ?? '');
            $onix->setHeader($header);
        }
    }
    
    /**
     * Validate state consistency
     */
    public function validate(): bool
    {
        // Basic validation rules
        if ($this->totalProductCount < 0 || $this->processedProductCount < 0 || $this->skippedProductCount < 0) {
            return false;
        }
        
        if ($this->processedProductCount > $this->totalProductCount) {
            return false;
        }
        
        if ($this->headerProcessed && $this->versionDetected && empty($this->onixVersion)) {
            return false;
        }
        
        return true;
    }
    
    // Getters
    public function hasNamespace(): bool { return $this->hasNamespace; }
    public function getNamespaceURI(): string { return $this->namespaceURI; }
    public function isHeaderProcessed(): bool { return $this->headerProcessed; }
    public function isVersionDetected(): bool { return $this->versionDetected; }
    public function getOnixVersion(): string { return $this->onixVersion; }
    public function getHeaderData(): array { return $this->headerData; }
    public function getTotalProductCount(): int { return $this->totalProductCount; }
    public function getProcessedProductCount(): int { return $this->processedProductCount; }
    public function getSkippedProductCount(): int { return $this->skippedProductCount; }
    public function getParsingPhase(): string { return $this->parsingPhase; }
    public function getContextData(): array { return $this->contextData; }
    public function getTimestamp(): int { return $this->timestamp; }
    
    // Setters for dynamic updates
    public function setContextData(array $data): void { $this->contextData = $data; }
    public function addContextData(string $key, $value): void { $this->contextData[$key] = $value; }
    public function setParsingPhase(string $phase): void { $this->parsingPhase = $phase; }
}