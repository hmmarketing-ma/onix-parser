<?php

namespace ONIXParser;

use ONIXParser\Model\Header;
use ONIXParser\Model\Onix;
use ONIXParser\Model\Product;
use ONIXParser\Model\Subject;
use ONIXParser\Model\Price;
use ONIXParser\Model\ResourceVersion;
use ONIXParser\Model\SupplyDetail;
use ONIXParser\Model\Tax;
use ONIXParser\Model\Territory;
use ONIXParser\Model\Title;
use ONIXParser\Logger;
use ONIXParser\FieldMappings;
use ONIXParser\CodeMaps;

/**
 * Enhanced ONIX Parser
 * 
 * Parses ONIX 3.0 XML files with support for both namespaced and non-namespaced XML.
 */
class OnixParser
{
    /** @var \DOMXPath */
    private $xpath;
    
    /** @var Logger */
    protected $logger;
    
    /** @var string */
    protected $xmlPath;
    
    /** @var bool */
    protected $hasNamespace = false;
    
    /** @var string */
    protected $namespaceURI = 'http://www.editeur.org/onix/3.0/reference';
    
    /** @var Onix */
    protected $onix;
    
    /** @var array Field mapping with both namespaced and non-namespaced XPaths */
    private $fieldMappings;
    
    /** @var array Maps of codes to human-readable descriptions */
    private $codeMaps;

        /**
     * @var array CLIL codes mapped to human-readable names
     */
    private $clilCodes = [];

    /**
     * @var array THEMA codes mapped to human-readable names
     */
    private $themaCodes = [];

    /**
     * Constructor
     *
     * @param Logger|null $logger Optional logger instance
     */
    public function __construct(?Logger $logger = null)
    {
        $this->logger = $logger ?: new Logger();
        $this->onix = new Onix();
        $this->fieldMappings = FieldMappings::getMappings();
        $this->codeMaps = CodeMaps::getAllMaps();

        // Load subject mapping files
        $this->loadSubjectMappings();
    }

    /**
     * Load subject mapping files
     */
    private function loadSubjectMappings()
    {
        // Load CLIL codes
        $clilPath = __DIR__ . '/../data/clil_codes.json';
        if (file_exists($clilPath)) {
            $clilData = json_decode(file_get_contents($clilPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($clilData['schemes']['29']['classifications'])) {
                    // New structured format
                    $this->clilCodes = $clilData['schemes']['29']['classifications'];
                    $this->logger->info("Loaded " . count($this->clilCodes) . " CLIL codes (hierarchical format)");
                } elseif (isset($clilData['codes'])) {
                    // Old simple format
                    $this->clilCodes = $clilData['codes'];
                    $this->logger->info("Loaded " . count($this->clilCodes) . " CLIL codes (simple format)");
                } else {
                    $this->logger->warning("Unknown CLIL codes JSON format");
                }
            } else {
                $this->logger->warning("Error parsing CLIL codes JSON: " . json_last_error_msg());
            }
        } else {
            $this->logger->warning("CLIL codes file not found: $clilPath");
        }
        
        // Load THEMA codes
        $themaPath = __DIR__ . '/../data/thema_codes.json';
        if (file_exists($themaPath)) {
            $themaData = json_decode(file_get_contents($themaPath), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                if (isset($themaData['schemes']) && isset($themaData['schemes']['93']['classifications'])) {
                    // New structured format
                    $this->themaCodes = $themaData['schemes']['93']['classifications'];
                    $this->logger->info("Loaded " . count($this->themaCodes) . " THEMA codes (hierarchical format)");
                } elseif (isset($themaData['codes'])) {
                    // Old simple format
                    $this->themaCodes = $themaData['codes'];
                    $this->logger->info("Loaded " . count($this->themaCodes) . " THEMA codes (simple format)");
                } else {
                    $this->logger->warning("Unknown THEMA codes JSON format");
                }
            } else {
                $this->logger->warning("Error parsing THEMA codes JSON: " . json_last_error_msg());
            }
        } else {
            $this->logger->warning("THEMA codes file not found: $themaPath");
        }
    }

    /**
     * Parse ONIX XML file
     *
     * @param string $xmlPath Path to XML file
     * @param bool $isFile Whether the input is a file path (true) or XML string (false)
     * @return Onix Parsed data as an Onix object
     * @throws \Exception
     */
    public function parseFile(string $xmlPath, bool $isFile = true): Onix
    {
        $this->xmlPath = $xmlPath;
        
        if ($isFile && !file_exists($xmlPath)) {
            throw new \Exception("XML file not found: $xmlPath");
        }

        // Enable user error handling
        $previous = libxml_use_internal_errors(true);

        try {
            // Load XML
            $dom = new \DOMDocument();
            
            if ($isFile) {
                $loaded = $dom->load($xmlPath, LIBXML_NONET);
            } else {
                $loaded = $dom->loadXML($xmlPath, LIBXML_NONET);
            }
            
            if (!$loaded) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                throw new \Exception('Invalid XML: ' . $this->formatLibXMLErrors($errors));
            }

            // Create XPath object
            $this->xpath = new \DOMXPath($dom);
            
            // Check if document has namespace
            $rootElement = $dom->documentElement;
            $rootNamespace = $rootElement->namespaceURI;
            
            $this->hasNamespace = !empty($rootNamespace);
            
            if ($this->hasNamespace) {
                $this->namespaceURI = $rootNamespace ?: 'http://www.editeur.org/onix/3.0/reference';
                $this->xpath->registerNamespace('onix', $this->namespaceURI);
                $this->logger->info("XML has namespace: " . $this->namespaceURI);
            } else {
                $this->logger->info("XML does not have a namespace");
            }

            // Parse message info
            $header = $this->parseMessageInfo();
            $this->onix->setHeader($header);
            
            // Detect ONIX version
            $this->detectAndSetVersion($dom);
            
            // Parse products
            $products = $this->parseProducts();
            
            foreach ($products as $product) {
                $this->onix->setProduct($product);
            }
            
            return $this->onix;
        } catch (\Exception $e) {
            $this->logger->error("Error parsing ONIX file: " . $e->getMessage());
            throw $e;
        } finally {
            // Restore previous error handling state
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Parse ONIX XML file using streaming approach for large files
     *
     * @param string $xmlPath Path to XML file
     * @param array $options Options for batch processing:
     *                      - limit: Maximum number of products to process (0 for all)
     *                      - offset: Number of products to skip before processing
     *                      - callback: Callback function to call for each product
     *                      - continue_on_error: Whether to continue processing on error
     * @return Onix Parsed data as an Onix object
     * @throws \Exception
     */
    public function parseFileStreaming(string $xmlPath, array $options = []): Onix
    {
        // Set default options
        $options = array_merge([
            'limit' => 0, // 0 means no limit
            'offset' => 0,
            'callback' => null,
            'continue_on_error' => true,
        ], $options);

        $this->xmlPath = $xmlPath;
        
        if (!file_exists($xmlPath)) {
            throw new \Exception("XML file not found: $xmlPath");
        }

        // Create a new Onix object
        $this->onix = new Onix();
        
        // Enable user error handling
        $previous = libxml_use_internal_errors(true);

        try {
            // Create XMLReader instance
            $reader = new \XMLReader();
            
            // Open the XML file
            if (!$reader->open($xmlPath, null, LIBXML_NONET)) {
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
                    // Convert current node to DOM element for processing
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
                    
                    try {
                        // Convert current node to DOM element for processing
                        $productNode = $this->getNodeFromReader($reader);
                        if ($productNode) {
                            // Parse the product using a custom method for streaming
                            $product = $this->parseProductStreaming($productNode);
                            
                            // Add to Onix object
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
        }
    }

    /**
     * Convert XMLReader node to DOMNode
     *
     * @param \XMLReader $reader
     * @return \DOMNode|null
     */
    protected function getNodeFromReader(\XMLReader $reader): ?\DOMNode
    {
        if ($reader->nodeType !== \XMLReader::ELEMENT) {
            return null;
        }
        
        // Create a new DOM document
        $dom = new \DOMDocument();
        
        // Import the current node
        $node = $reader->expand($dom);
        
        if (!$node) {
            return null;
        }
        
        return $node;
    }

    /**
     * Parse header information from a DOM node
     *
     * @param \DOMNode $headerNode
     * @return Header
     */
    protected function parseHeaderFromNode(\DOMNode $headerNode): Header
    {
        $header = new Header();
        
        // Create a temporary DOMXPath for this header node
        $tempDoc = new \DOMDocument();
        $tempDoc->appendChild($tempDoc->importNode($headerNode, true));
        $tempXpath = new \DOMXPath($tempDoc);
        
        // Register namespace if needed
        if ($this->hasNamespace) {
            $tempXpath->registerNamespace('onix', $this->namespaceURI);
        }
        
        // Helper function to get node value
        $getNodeValue = function($xpaths) use ($tempXpath) {
            foreach ($xpaths as $xpath) {
                try {
                    $nodes = $tempXpath->query($xpath);
                    if ($nodes && $nodes->length > 0) {
                        return trim($nodes->item(0)->nodeValue);
                    }
                } catch (\Exception $e) {
                    // Skip to next xpath
                }
            }
            return null;
        };
        
        // Parse header fields
        $sender = $getNodeValue($this->fieldMappings['header']['sender']);
        $header->setSender($sender);
        
        $contact = $getNodeValue($this->fieldMappings['header']['contact']);
        $header->setContact($contact);
        
        $email = $getNodeValue($this->fieldMappings['header']['email']);
        $header->setEmail($email);
        
        $sentDateTime = $getNodeValue($this->fieldMappings['header']['sent_date']);
        $header->setSentDateTime($this->formatDate($sentDateTime));
        
        // Store original XML
        $headerXml = new \SimpleXMLElement($headerNode->ownerDocument->saveXML($headerNode));
        $header->setXml($headerXml);
        
        return $header;
    }

    /**
     * Parse a single product for streaming
     * This method uses the same parsing methods as the regular method but with a local XPath context
     *
     * @param \DOMNode $productNode
     * @return Product
     */
    public function parseProductStreaming(\DOMNode $productNode): Product
    {
        $product = new Product();
        
        // Create a new DOM document and import the product node
        $dom = new \DOMDocument();
        $importedNode = $dom->importNode($productNode, true);
        $dom->appendChild($importedNode);
        
        // Create a new XPath object for this document
        $xpath = new \DOMXPath($dom);
        
        // Auto-detect namespace in this fragment (for ChunkOnixParser compatibility)
        $fragmentHasNamespace = !empty($importedNode->namespaceURI);
        $fragmentNamespaceURI = $fragmentHasNamespace ? $importedNode->namespaceURI : $this->namespaceURI;
        
        // Register namespace if needed (either global or fragment-specific)
        if ($this->hasNamespace || $fragmentHasNamespace) {
            $xpath->registerNamespace('onix', $fragmentNamespaceURI);
        }
        
        // Set record reference
        $recordReference = $this->getNodeValueWithContext($this->fieldMappings['record_reference'], $importedNode, $xpath);
        $product->setRecordReference($recordReference);
        
        // Set notification type
        $this->parseNotification($importedNode, $product, $xpath);
        
        // Parse identifiers (ISBN, EAN, etc.)
        $this->parseIdentifiers($importedNode, $product, $xpath);
        
        // Parse product form
        $this->parseProductForm($importedNode, $product, $xpath);
        
        // Parse title information
        $this->parseTitle($importedNode, $product, $xpath);
        
        // Parse subjects
        $this->parseSubjects($importedNode, $product, $xpath);
        
        // Parse descriptions
        $this->parseDescriptions($importedNode, $product, $xpath);
        
        // Parse images and resources
        $this->parseImages($importedNode, $product, $xpath);
        
        // Parse collections
        $this->parseCollections($importedNode, $product, $xpath);
        
        // Parse contributors (authors, editors, etc.)
        $this->parseContributors($importedNode, $product, $xpath);
        
        // Parse publisher information
        $this->parsePublisher($importedNode, $product, $xpath);
        
        // Parse publication dates
        $this->parsePublicationDates($importedNode, $product, $xpath);
        
        // Parse measures and extents (height, width, thickness, weight, pages)
        $this->parsePhysical($importedNode, $product, $xpath);

        // Which of the six ONIX 3.0 blocks did this record contain?
        $this->parsePresentBlocks($importedNode, $product);

        // Parse supply details (availability, prices)
        $this->parseSupply($importedNode, $product, $xpath);
        
        // Store original XML with proper namespace registration
        $xmlString = $dom->saveXML($importedNode);
        
        // Ensure namespace is declared in the XML string for SimpleXMLElement
        if (($this->hasNamespace || $fragmentHasNamespace) && strpos($xmlString, 'xmlns=') === false) {
            $xmlString = str_replace('<Product>', '<Product xmlns="' . $fragmentNamespaceURI . '">', $xmlString);
        }
        
        $productXml = new \SimpleXMLElement($xmlString);
        
        // Register namespace for SimpleXMLElement xpath operations
        if ($this->hasNamespace || $fragmentHasNamespace) {
            $productXml->registerXPathNamespace('onix', $fragmentNamespaceURI);
        }
        
        $product->setXml($productXml);
        
        return $product;
    }

   
    
    /**
     * Detect and set ONIX version
     *
     * @param \DOMDocument $dom
     */
    private function detectAndSetVersion(\DOMDocument $dom)
    {
        $rootElement = $dom->documentElement;
        
        if ($rootElement->hasAttribute('release')) {
            $release = $rootElement->getAttribute('release');
            $this->onix->setVersion('3.' . $release);
        } else {
            // Default to 3.0 if not specified
            $this->onix->setVersion('3.0');
        }
    }
    
    /**
     * Format libxml errors into readable string
     *
     * @param array $errors
     * @return string
     */
    protected function formatLibXMLErrors(array $errors): string
    {
        $messages = [];
        foreach ($errors as $error) {
            $messages[] = sprintf(
                '[%s %d:%d] %s',
                $error->level == LIBXML_ERR_WARNING ? 'Warning' : 'Error',
                $error->line,
                $error->column,
                trim($error->message)
            );
        }
        return implode('; ', $messages);
    }

    /**
     * Parse message information
     *
     * @return Header
     */
    private function parseMessageInfo(): Header
    {
        $header = new Header();
        
        $sender = $this->getNodeValue($this->fieldMappings['header']['sender']);
        $header->setSender($sender);
        
        $contact = $this->getNodeValue($this->fieldMappings['header']['contact']);
        $header->setContact($contact);
        
        $email = $this->getNodeValue($this->fieldMappings['header']['email']);
        $header->setEmail($email);
        
        $sentDateTime = $this->getNodeValue($this->fieldMappings['header']['sent_date']);
        $header->setSentDateTime($this->formatDate($sentDateTime));
        
        // Store original XML
        $headerNode = $this->queryNode($this->fieldMappings['header']['node']);
        if ($headerNode) {
            $headerXml = new \SimpleXMLElement($headerNode->ownerDocument->saveXML($headerNode));
            $header->setXml($headerXml);
        }
        
        return $header;
    }

    /**
     * Parse all products
     *
     * @return array<Product>
     */
    private function parseProducts(): array
    {
        $products = [];
        
        // Get all product nodes
        $productNodes = $this->queryNodes($this->fieldMappings['products']);
        
        if (empty($productNodes)) {
            $this->logger->warning("No product nodes found in XML");
            return [];
        }
        
        $this->logger->info("Found " . count($productNodes) . " product nodes");
        
        foreach ($productNodes as $index => $productNode) {
            try {
                $recordReference = $this->getNodeValue($this->fieldMappings['record_reference'], $productNode);
                
                $product = $this->parseProduct($productNode);
                $products[] = $product;
                
                $this->logger->info("Successfully parsed product: " . $product->getRecordReference());
            } catch (\Exception $e) {
                $this->logger->error("Error parsing product #" . ($index+1) . ": " . $e->getMessage());
            }
        }
        
        return $products;
    }

    /**
     * Parse a single product
     *
     * @param \DOMNode $productNode
     * @return Product
     */
    private function parseProduct(\DOMNode $productNode): Product
    {
        $product = new Product();
        
        // Set record reference
        $recordReference = $this->getNodeValue($this->fieldMappings['record_reference'], $productNode);
        $product->setRecordReference($recordReference);
        
        // Set notification type
        $this->parseNotification($productNode, $product);
        
        // Parse identifiers (ISBN, EAN, etc.)
        $this->parseIdentifiers($productNode, $product);

        // Parse product form - ADDED THIS LINE
        $this->parseProductForm($productNode, $product);
        
        // Parse title information
        $this->parseTitle($productNode, $product);

        // Parse title subjects
        $this->parseSubjects($productNode, $product);

        // Parse descriptions
        $this->parseDescriptions($productNode, $product);

        // Parse images and resources
        $this->parseImages($productNode, $product);

        // Parse collections
        $this->parseCollections($productNode, $product);
        
        // Parse contributors (authors, editors, etc.)
        $this->parseContributors($productNode, $product);
        
        // Parse publisher information
        $this->parsePublisher($productNode, $product);
        
        // Parse publication dates
        $this->parsePublicationDates($productNode, $product);
        
        // Parse measures and extents (height, width, thickness, weight, pages)
        $this->parsePhysical($productNode, $product);

        // Which of the six ONIX 3.0 blocks did this record contain?
        $this->parsePresentBlocks($productNode, $product);

        // Parse supply details (availability, prices)
        $this->parseSupply($productNode, $product);
        
        // Store original XML with proper namespace registration
        $xmlString = $productNode->ownerDocument->saveXML($productNode);
        
        // Ensure namespace is declared in the XML string for SimpleXMLElement
        if ($this->hasNamespace && strpos($xmlString, 'xmlns=') === false) {
            $xmlString = str_replace('<Product>', '<Product xmlns="' . $this->namespaceURI . '">', $xmlString);
        }
        
        $productXml = new \SimpleXMLElement($xmlString);
        
        // Register namespace for SimpleXMLElement xpath operations
        if ($this->hasNamespace) {
            $productXml->registerXPathNamespace('onix', $this->namespaceURI);
        }
        
        $product->setXml($productXml);
        
        return $product;
    }

    /**
     * Parse notification type
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseNotification(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        $type = $this->getNodeValueWithContext($this->fieldMappings['notification']['type'], $productNode, $localXpath);
        $deletionText = $this->getNodeValueWithContext($this->fieldMappings['notification']['deletion_text'], $productNode, $localXpath);
        
        if ($type) {
            $product->setNotificationType($type);
            $product->setNotificationTypeName($this->codeMaps['notification_type'][$type] ?? 'unknown');
        }
        
        if ($deletionText) {
            $product->setDeletionText($deletionText);
        }
    }

    /**
     * Parse identifiers
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseIdentifiers(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        foreach ($this->fieldMappings['identifiers'] as $key => $xpath) {
            $value = $this->getNodeValueWithContext($xpath, $productNode, $localXpath);
            if ($value) {
                // Set specific identifiers directly on product for convenience
                switch ($key) {
                    case 'isbn':
                        $product->setIsbn($value);
                        break;
                    case 'ean':
                        $product->setEan($value);
                        break;
                }
            }
        }
    }

    /**
     * Parse title information
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseTitle(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        $titleText = $this->getNodeValueWithContext($this->fieldMappings['title']['main'], $productNode, $localXpath);
        $subtitle = $this->getNodeValueWithContext($this->fieldMappings['title']['subtitle'], $productNode, $localXpath);
        
        if ($titleText) {
            $title = new Title();
            $title->setText($titleText);
            
            if ($subtitle) {
                $title->setSubtitle($subtitle);
            }

            // textcase / language: attribute of TitleText, or element
            $attrs = $this->fieldMappings['title_text_attrs'];
            $title->setTextCase($this->getNodeValueWithContext($attrs['text_case'], $productNode, $localXpath) ?: null);
            $title->setLanguage($this->getNodeValueWithContext($attrs['language'], $productNode, $localXpath) ?: null);
            
            $product->setTitle($title);
        }
    }


    /**
     * Parse subjects
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseSubjects(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        // Get subject nodes
        $subjectNodes = $this->queryNodesWithContext($this->fieldMappings['subjects']['nodes'], $productNode, $localXpath);
        
        foreach ($subjectNodes as $subjectNode) {
            $subject = new Subject();
            
            // Check if this is the main subject
            $mainSubject = $this->queryNodeWithContext($this->fieldMappings['subjects']['main_subject'], $subjectNode, $localXpath) !== null;
            $subject->setMainSubject($mainSubject);
            
            // Get scheme identifier
            $schemeIdentifier = $this->getNodeValueWithContext($this->fieldMappings['subjects']['scheme_identifier'], $subjectNode, $localXpath);
            if ($schemeIdentifier) {
                $subject->setScheme($schemeIdentifier);
            } else {
                continue; // Skip if no scheme identifier
            }
            
            // Get subject code
            $code = $this->getNodeValueWithContext($this->fieldMappings['subjects']['code'], $subjectNode, $localXpath);
            if ($code) {
                $subject->setCode($code);
                
                // Set heading text and other properties based on scheme and code
                if ($schemeIdentifier === '29' && isset($this->clilCodes[$code])) {
                    // CLIL code in new format
                    $clilInfo = $this->clilCodes[$code];
                    
                    // Set basic heading text (preferring French label)
                    if (isset($clilInfo['label_fr'])) {
                        $subject->setHeadingText($clilInfo['label_fr']);
                        $subject->setLanguage('fr');
                    } elseif (isset($clilInfo['label_en'])) {
                        $subject->setHeadingText($clilInfo['label_en']);
                        $subject->setLanguage('en');
                    }
                    
                    // Set description if available
                    if (isset($clilInfo['description'])) {
                        $subject->setDescription(strip_tags($clilInfo['description']));
                    }
                    
                    // Set parent and child relationships if available
                    if (isset($clilInfo['parents']) && is_array($clilInfo['parents'])) {
                        $subject->setParents($clilInfo['parents']);
                    }
                    
                    if (isset($clilInfo['children']) && is_array($clilInfo['children'])) {
                        $subject->setChildren($clilInfo['children']);
                    }
                } elseif (in_array($schemeIdentifier, ['93', '94', '95', '96', '97', '98', '99']) && 
                          isset($this->themaCodes[$code])) {
                    // THEMA code in new format
                    $themaInfo = $this->themaCodes[$code];
                    
                    // Set basic heading text (preferring French label)
                    if (isset($themaInfo['label_fr'])) {
                        $subject->setHeadingText($themaInfo['label_fr']);
                        $subject->setLanguage('fr');
                    } elseif (isset($themaInfo['label_en'])) {
                        $subject->setHeadingText($themaInfo['label_en']);
                        $subject->setLanguage('en');
                    }
                    
                    // Set description if available
                    if (isset($themaInfo['description'])) {
                        $subject->setDescription(strip_tags($themaInfo['description']));
                    }
                    
                    // Set parent and child relationships if available
                    if (isset($themaInfo['parents']) && is_array($themaInfo['parents'])) {
                        $subject->setParents($themaInfo['parents']);
                    }
                    
                    if (isset($themaInfo['children']) && is_array($themaInfo['children'])) {
                        $subject->setChildren($themaInfo['children']);
                    }
                }
            }
            
            // Get explicit heading text (especially for ScoLOMFR)
            $headingText = $this->getNodeValueWithContext($this->fieldMappings['subjects']['heading_text'], $subjectNode, $localXpath);
            if ($headingText) {
                $subject->setHeadingText($headingText);
            }
            
            // Add subject to product
            $product->addSubject($subject);
            
            // Log subject information
            $this->logger->debug("Added subject: " . $subject->getScheme() . " - " . $subject->getCode() . 
                              ($subject->getHeadingText() ? " - " . $subject->getHeadingText() : "") .
                              ($subject->isMainSubject() ? " (main subject)" : ""));
        }
    }

    /**
     * Get information about a subject code
     * 
     * @param string $code Subject code
     * @param string $scheme Subject scheme ('29' for CLIL, '93'-'99' for THEMA)
     * @return array|null Information about the subject or null if not found
     */
    public function getSubjectInfo($code, $scheme)
    {
        if ($scheme === '29' && isset($this->clilCodes[$code])) {
            $info = $this->clilCodes[$code];
            return [
                'code' => $code,
                'label' => isset($info['label_fr']) ? $info['label_fr'] : 
                        (isset($info['label_en']) ? $info['label_en'] : 'Unknown'),
                'description' => isset($info['description']) ? strip_tags($info['description']) : null,
                'parents' => isset($info['parents']) ? $info['parents'] : [],
                'children' => isset($info['children']) ? $info['children'] : []
            ];
        } elseif (in_array($scheme, ['93', '94', '95', '96', '97', '98', '99']) && 
                isset($this->themaCodes[$code])) {
            $info = $this->themaCodes[$code];
            return [
                'code' => $code,
                'label' => isset($info['label_fr']) ? $info['label_fr'] : 
                        (isset($info['label_en']) ? $info['label_en'] : 'Unknown'),
                'description' => isset($info['description']) ? strip_tags($info['description']) : null,
                'parents' => isset($info['parents']) ? $info['parents'] : [],
                'children' => isset($info['children']) ? $info['children'] : []
            ];
        }
        
        return null;
    }


    /**
     * Parse descriptions
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseDescriptions(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        // Get text content nodes
        $textNodes = $this->queryNodesWithContext($this->fieldMappings['description']['text_nodes'], $productNode, $localXpath);
        
        foreach ($textNodes as $textNode) {
            try {
                $description = new \ONIXParser\Model\Description();
                
                // Get text type
                $textType = $this->getNodeValueWithContext($this->fieldMappings['description']['text_type'], $textNode, $localXpath);
                if ($textType) {
                    $description->setType($textType);
                    
                    // Set type name using code map
                    if (isset($this->codeMaps['text_type'][$textType])) {
                        $description->setTypeName($this->codeMaps['text_type'][$textType]);
                    }
                }
                
                // Get text format
                $textFormat = $this->getNodeValueWithContext($this->fieldMappings['description']['text_format'], $textNode, $localXpath);
                if ($textFormat) {
                    $description->setFormat($textFormat);
                    
                    // Set format name using code map if available
                    if (isset($this->codeMaps['text_format'][$textFormat])) {
                        $description->setFormatName($this->codeMaps['text_format'][$textFormat]);
                    }
                }
                
                // Language of the text (language attribute of Text, or element)
                $textLanguage = $this->getNodeValueWithContext($this->fieldMappings['description']['text_language'], $textNode, $localXpath);
                if ($textLanguage) {
                    $description->setLanguage($textLanguage);
                }

                // Get text content
                $textContent = $this->getNodeValueWithContext($this->fieldMappings['description']['text_content'], $textNode, $localXpath);
                if ($textContent) {
                    $description->setContent($textContent);
                    
                    // Only add descriptions with content
                    $product->addDescription($description);
                    
                    $this->logger->debug(
                        "Added description: " . 
                        ($description->getTypeName() ?: $description->getType()) . 
                        " - " . ($description->getFormatName() ?: $description->getFormat())
                    );
                }
                
            } catch (\Exception $e) {
                $this->logger->warning("Error parsing description: " . $e->getMessage());
            }
        }
    }

    /**
     * Parse images and resources
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseImages(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        // Get image nodes
        $imageNodes = $this->queryNodesWithContext($this->fieldMappings['images']['nodes'], $productNode, $localXpath);
        
        foreach ($imageNodes as $imageNode) {
            $this->parseResourceVersions($imageNode, $product, $localXpath);
            try {
                $image = new \ONIXParser\Model\Image();
                
                // Get content type
                $contentType = $this->getNodeValueWithContext($this->fieldMappings['images']['content_type'], $imageNode, $localXpath);
                if ($contentType) {
                    $image->setContentType($contentType);
                    
                    // Set content type name using code map
                    if (isset($this->codeMaps['resource_content_type'][$contentType])) {
                        $image->setContentTypeName($this->codeMaps['resource_content_type'][$contentType]);
                    }
                }
                
                // Get resource mode
                $mode = $this->getNodeValueWithContext($this->fieldMappings['images']['mode'], $imageNode, $localXpath);
                if ($mode) {
                    $image->setMode($mode);
                    
                    // Set mode name using code map
                    if (isset($this->codeMaps['resource_mode'][$mode])) {
                        $image->setModeName($this->codeMaps['resource_mode'][$mode]);
                    }
                }
                
                // Get URL
                $url = $this->getNodeValueWithContext($this->fieldMappings['images']['url'], $imageNode, $localXpath);
                if ($url) {
                    $image->setUrl($url);
                    
                    // Only add images with valid URLs
                    if ($image->hasValidUrl()) {
                        $product->addImage($image);
                        
                        $this->logger->debug(
                            "Added image: " . 
                            ($image->getContentTypeName() ?: $image->getContentType()) . 
                            " - " . ($image->getModeName() ?: $image->getMode()) . 
                            " - " . $image->getUrl()
                        );
                    } else {
                        $this->logger->warning("Skipped image with invalid URL: " . $url);
                    }
                }
                
            } catch (\Exception $e) {
                $this->logger->warning("Error parsing image: " . $e->getMessage());
            }
        }
    }

    /**
     * Parse every ResourceVersion of one SupportingResource into ResourceVersion objects:
     * content type, mode, form, URL, features (height, width, bytes ...) and ContentDates.
     *
     * @param \DOMNode $resourceNode a SupportingResource
     * @param Product $product
     * @param \DOMXPath|null $localXpath
     */
    private function parseResourceVersions(\DOMNode $resourceNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        $map = $this->fieldMappings['images'];
        $contentType = $this->getNodeValueWithContext($map['content_type'], $resourceNode, $localXpath);
        $mode = $this->getNodeValueWithContext($map['mode'], $resourceNode, $localXpath);

        foreach ($this->queryNodesWithContext($map['versions'], $resourceNode, $localXpath) as $versionNode) {
            $version = new ResourceVersion();
            $version->setContentType($contentType ?: null)
                ->setMode($mode ?: null)
                ->setForm($this->getNodeValueWithContext($map['version_form'], $versionNode, $localXpath) ?: null)
                ->setUrl($this->getNodeValueWithContext($map['version_link'], $versionNode, $localXpath) ?: null);

            foreach ($this->queryNodesWithContext($map['version_features'], $versionNode, $localXpath) as $featureNode) {
                $version->addFeature(
                    $this->getNodeValueWithContext($map['feature_type'], $featureNode, $localXpath),
                    $this->getNodeValueWithContext($map['feature_value'], $featureNode, $localXpath)
                );
            }
            foreach ($this->readDateComposites($map['content_dates'], $map['content_date_role'], $versionNode, $localXpath) as $d) {
                $version->addContentDate($d['role'], $d['format'], $d['date']);
            }

            $product->addResourceVersion($version);
        }
    }

    /**
     * Parse collections
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseCollections(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        // First check if there's a NoCollection flag
        $noCollection = $this->queryNodeWithContext($this->fieldMappings['no_collection'], $productNode, $localXpath);
        if ($noCollection) {
            $this->logger->debug("Product explicitly has no collections");
            return;
        }

        // Get collection nodes
        $collectionNodes = $this->queryNodesWithContext($this->fieldMappings['collections']['nodes'], $productNode, $localXpath);
        
        foreach ($collectionNodes as $collectionNode) {
            try {
                $collection = new \ONIXParser\Model\Collection();
                
                // Get collection type
                $type = $this->getNodeValueWithContext($this->fieldMappings['collections']['type'], $collectionNode, $localXpath);
                if ($type) {
                    $collection->setType($type);
                    
                    // Set type name using code map if available
                    if (isset($this->codeMaps['collection_type'][$type])) {
                        $collection->setTypeName($this->codeMaps['collection_type'][$type]);
                    }
                }
                
                // Process title detail nodes
                $titleDetailNodes = $this->queryNodesWithContext($this->fieldMappings['collections']['title_details'], $collectionNode, $localXpath);
                $hasPrimaryTitle = false;
                
                foreach ($titleDetailNodes as $titleDetailNode) {
                    // Get title type
                    $titleType = $this->getNodeValueWithContext($this->fieldMappings['collections']['title_type'], $titleDetailNode, $localXpath);
                    
                    // Process title elements
                    $titleElementNodes = $this->queryNodesWithContext($this->fieldMappings['collections']['title_elements'], $titleDetailNode, $localXpath);
                    
                    foreach ($titleElementNodes as $titleElementNode) {
                        // Get title level
                        $titleLevel = $this->getNodeValueWithContext($this->fieldMappings['collections']['title_level'], $titleElementNode, $localXpath);
                        
                        // Get title text
                        $titleText = $this->getNodeValueWithContext($this->fieldMappings['collections']['title_text'], $titleElementNode, $localXpath);
                        
                        if ($titleText) {
                            // If this is the preferred title (type 01, level 02), set it as the main title
                            if ($titleType === '01' && $titleLevel === '02' && !$hasPrimaryTitle) {
                                $collection->setTitleText($titleText);
                                $hasPrimaryTitle = true;
                            } else {
                                // Otherwise, store as additional title
                                $collection->addAdditionalTitle($titleType, $titleLevel, $titleText);
                            }
                        }
                        
                        // Get part number if available
                        $partNumber = $this->getNodeValueWithContext($this->fieldMappings['collections']['part_number'], $titleElementNode, $localXpath);
                        if ($partNumber) {
                            $collection->setPartNumber($partNumber);
                        }
                    }
                }
                
                // Only add collections with a title
                if ($collection->getTitleText()) {
                    $product->addCollection($collection);
                    
                    $this->logger->debug(
                        "Added collection: " . 
                        ($collection->getTypeName() ?: $collection->getType()) . 
                        " - " . $collection->getTitleText() .
                        ($collection->getPartNumber() ? " (Part " . $collection->getPartNumber() . ")" : "")
                    );
                }
                
            } catch (\Exception $e) {
                $this->logger->warning("Error parsing collection: " . $e->getMessage());
            }
        }
    }

    /**
     * Parse product form
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseProductForm(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        $formCode = $this->getNodeValueWithContext($this->fieldMappings['product_form']['form_code'], $productNode, $localXpath);
        
        if ($formCode) {
            $product->setProductForm($formCode);
            
            // Set product form name using code map
            if (isset($this->codeMaps['product_form'][$formCode])) {
                $product->setProductFormName($this->codeMaps['product_form'][$formCode]);
            }
        }
    }

    /**
     * Record the ONIX 3.0 blocks that are direct children of the Product element.
     * Matches on the local name, so it works with or without a namespace.
     *
     * @param \DOMNode $productNode
     * @param Product $product
     */
    private function parsePresentBlocks(\DOMNode $productNode, Product $product): void
    {
        $found = [];
        foreach ($productNode->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE && in_array($child->localName, Product::BLOCKS, true)) {
                $found[] = $child->localName;
            }
        }
        $product->setPresentBlocks($found);
    }

    /**
     * Parse measures (MeasureType / Measurement / MeasureUnitCode) and extents
     * (ExtentType / ExtentValue / ExtentUnit). The values are read here, with the
     * namespace-aware XPath of this parser, and stored raw on the Product.
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath
     */
    private function parsePhysical(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        $map = $this->fieldMappings['physical'];

        $measures = [];
        foreach ($this->queryNodesWithContext($map['measures'], $productNode, $localXpath) as $node) {
            $measures[] = [
                'type' => $this->getNodeValueWithContext($map['measure_type'], $node, $localXpath),
                'value' => $this->getNodeValueWithContext($map['measure_value'], $node, $localXpath),
                'unit' => $this->getNodeValueWithContext($map['measure_unit'], $node, $localXpath),
            ];
        }
        $product->setMeasures($measures);

        $extents = [];
        foreach ($this->queryNodesWithContext($map['extents'], $productNode, $localXpath) as $node) {
            $extents[] = [
                'type' => $this->getNodeValueWithContext($map['extent_type'], $node, $localXpath),
                'value' => $this->getNodeValueWithContext($map['extent_value'], $node, $localXpath),
                'unit' => $this->getNodeValueWithContext($map['extent_unit'], $node, $localXpath),
            ];
        }
        $product->setExtents($extents);
    }

    /**
     * Parse supply information
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseSupply(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        // Get supply details
        $supplyDetailNodes = $this->queryNodesWithContext($this->fieldMappings['supply']['supply_details'], $productNode, $localXpath);
        $supplyMap = $this->fieldMappings['supply'];

        // Market territories of the ProductSupply (where the product is on sale)
        foreach ($this->queryNodesWithContext($supplyMap['market_territories'], $productNode, $localXpath) as $marketTerritory) {
            $product->addMarketTerritory($this->parseTerritory($marketTerritory, $localXpath));
        }
        
        foreach ($supplyDetailNodes as $supplyNode) {
            // Full model: one SupplyDetail object per <SupplyDetail>, nothing merged
            $detail = new SupplyDetail();

            // Parse supplier information
            $supplierName = $this->getNodeValueWithContext($supplyMap['supplier_name'], $supplyNode, $localXpath);
            $supplierRole = $this->getNodeValueWithContext($supplyMap['supplier_role'], $supplyNode, $localXpath);
            $detail->setSupplierName($supplierName ?: null);
            $detail->setSupplierRole($supplierRole ?: null);

            foreach ($this->queryNodesWithContext($supplyMap['supplier_identifiers'], $supplyNode, $localXpath) as $idNode) {
                $idType = $this->getNodeValueWithContext($supplyMap['supplier_id_type'], $idNode, $localXpath);
                $idValue = $this->getNodeValueWithContext($supplyMap['supplier_id_value'], $idNode, $localXpath);
                if ($idType !== null && $idValue !== null && $idValue !== '') {
                    $detail->addSupplierIdentifier($idType, $idValue);
                }
            }

            // Supplier GLN (SupplierIDType 06)
            $supplierGLN = $this->getNodeValueWithContext($supplyMap['gln'], $supplyNode, $localXpath);
            $detail->setSupplierGln($supplierGLN ?: null);

            // Availability (List 65), exactly as sent
            $availability = $this->getNodeValueWithContext($this->fieldMappings['stock']['availability'], $supplyNode, $localXpath);
            $detail->setAvailability($availability ?: null);

            // Returns, supply dates, order time, unpriced item type
            foreach ($this->queryNodesWithContext($supplyMap['returns_conditions'], $supplyNode, $localXpath) as $retNode) {
                $detail->addReturnsConditions(
                    $this->getNodeValueWithContext($supplyMap['returns_code_type'], $retNode, $localXpath),
                    $this->getNodeValueWithContext($supplyMap['returns_code'], $retNode, $localXpath)
                );
            }
            foreach ($this->readDateComposites($supplyMap['supply_dates'], $supplyMap['supply_date_role'], $supplyNode, $localXpath) as $d) {
                $detail->addSupplyDate($d['role'], $d['format'], $d['date']);
            }
            $detail->setOrderTime($this->getNodeValueWithContext($supplyMap['order_time'], $supplyNode, $localXpath));
            $detail->setUnpricedItemType($this->getNodeValueWithContext($supplyMap['unpriced_item_type'], $supplyNode, $localXpath));

            // Legacy single-value getters: the last SupplyDetail still wins here (old behaviour, kept)
            if ($supplierName) {
                $product->setSupplierName($supplierName);
            }
            if ($supplierRole) {
                $product->setSupplierRole($supplierRole);
            }
            if ($availability) {
                $product->setAvailabilityCode($availability);
                
                // Use the availability code map for status
                $availabilityStatus = $this->codeMaps['availability_code'][$availability] ?? 'unknown';
                $product->setAvailable($availabilityStatus === 'available');
            }
            if ($supplierGLN) {
                $product->setSupplierGLN($supplierGLN);
            }
            
            // Parse prices
            $priceNodes = $this->queryNodesWithContext($this->fieldMappings['pricing']['price_nodes'], $supplyNode, $localXpath);
            
            foreach ($priceNodes as $priceNode) {
                $price = $this->parsePrice($priceNode, $localXpath);
                $detail->addPrice($price);

                // Legacy flat list: only prices that have an amount
                if ($price->getAmount() !== null) {
                    $product->addPrice($price);
                }
            }

            $product->addSupplyDetail($detail);
        }
    }

    /**
     * Parse one <Price> composite.
     *
     * @param \DOMNode $priceNode
     * @param \DOMXPath|null $localXpath
     * @return Price
     */
    private function parsePrice(\DOMNode $priceNode, ?\DOMXPath $localXpath = null): Price
    {
        $map = $this->fieldMappings['pricing'];
        $price = new Price();

        // Tax information (first <Tax>, legacy getters)
        $taxType = $this->getNodeValueWithContext($map['tax_type'], $priceNode, $localXpath);
        if ($taxType) {
            $price->setTaxType($taxType);
        }

        $taxRateCode = $this->getNodeValueWithContext($map['tax_rate_code'], $priceNode, $localXpath);
        if ($taxRateCode) {
            $price->setTaxRateCode($taxRateCode);
        }

        // Price type
        $priceType = $this->getNodeValueWithContext($map['price_type'], $priceNode, $localXpath);
        if ($priceType) {
            $price->setType($priceType);
        }

        // Price amount and currency
        $priceAmount = $this->getNodeValueWithContext($map['price_amount'], $priceNode, $localXpath);
        $currency = $this->getNodeValueWithContext($map['currency_code'], $priceNode, $localXpath);

        if ($priceAmount) {
            $price->setAmount((float)$priceAmount);

            // Tax rate (first <Tax>, legacy getter)
            $taxRate = $this->getNodeValueWithContext($map['tax_rate_percent'], $priceNode, $localXpath);
            if ($taxRate) {
                $price->setTaxRate((float)$taxRate);
            }
        }
        if ($currency) {
            $price->setCurrency($currency);
        }

        // Full model: qualifier, status, dates, territory, all taxes, discount codes, unpriced type
        $price->setPriceQualifier($this->getNodeValueWithContext($map['price_qualifier'], $priceNode, $localXpath));
        $price->setPriceStatus($this->getNodeValueWithContext($map['price_status'], $priceNode, $localXpath));
        $price->setUnpricedItemType($this->getNodeValueWithContext($map['unpriced_item_type'], $priceNode, $localXpath));

        foreach ($this->readDateComposites($map['price_dates'], $map['price_date_role'], $priceNode, $localXpath) as $d) {
            $price->addDate($d['role'], $d['format'], $d['date']);
        }

        $territoryNode = $this->queryNodeWithContext($map['territory'], $priceNode, $localXpath);
        if ($territoryNode) {
            $price->setTerritory($this->parseTerritory($territoryNode, $localXpath));
        }

        foreach ($this->queryNodesWithContext($map['taxes'], $priceNode, $localXpath) as $taxNode) {
            $price->addTax(new Tax(
                $this->getNodeValueWithContext($map['tax_type_of'], $taxNode, $localXpath),
                $this->getNodeValueWithContext($map['tax_rate_code_of'], $taxNode, $localXpath),
                $this->getNodeValueWithContext($map['tax_rate_percent_of'], $taxNode, $localXpath),
                $this->getNodeValueWithContext($map['taxable_amount_of'], $taxNode, $localXpath),
                $this->getNodeValueWithContext($map['tax_amount_of'], $taxNode, $localXpath)
            ));
        }

        foreach ($this->queryNodesWithContext($map['discounts'], $priceNode, $localXpath) as $discountNode) {
            $price->addDiscountCode(
                $this->getNodeValueWithContext($map['discount_type'], $discountNode, $localXpath),
                $this->getNodeValueWithContext($map['discount_type_name'], $discountNode, $localXpath),
                $this->getNodeValueWithContext($map['discount_code'], $discountNode, $localXpath)
            );
        }

        return $price;
    }

    /**
     * Parse a <Territory> composite.
     */
    private function parseTerritory(\DOMNode $territoryNode, ?\DOMXPath $localXpath = null): Territory
    {
        $map = $this->fieldMappings['pricing'];
        return new Territory(
            $this->getNodeValueWithContext($map['countries_included'], $territoryNode, $localXpath),
            $this->getNodeValueWithContext($map['regions_included'], $territoryNode, $localXpath),
            $this->getNodeValueWithContext($map['countries_excluded'], $territoryNode, $localXpath),
            $this->getNodeValueWithContext($map['regions_excluded'], $territoryNode, $localXpath)
        );
    }

    /**
     * Read date composites such as <SupplyDate><SupplyDateRole/><DateFormat/><Date/></SupplyDate>.
     * The format is read from the <DateFormat> element, or from the dateformat attribute of <Date>.
     * The date is returned exactly as sent.
     *
     * @param array $nodePaths XPaths of the composites, relative to $contextNode
     * @param array $rolePaths XPaths of the role element, relative to a composite
     * @return array<int,array{role:?string,format:?string,date:?string}>
     */
    private function readDateComposites(array $nodePaths, array $rolePaths, \DOMNode $contextNode, ?\DOMXPath $localXpath = null): array
    {
        $out = [];
        $supplyMap = $this->fieldMappings['supply'];
        foreach ($this->queryNodesWithContext($nodePaths, $contextNode, $localXpath) as $node) {
            $format = $this->getNodeValueWithContext($supplyMap['date_format_element'], $node, $localXpath)
                ?: $this->getNodeValueWithContext($supplyMap['date_format_attribute'], $node, $localXpath);
            $out[] = [
                'role' => $this->getNodeValueWithContext($rolePaths, $node, $localXpath),
                'format' => $format ?: null,
                'date' => $this->getNodeValueWithContext($supplyMap['date_value'], $node, $localXpath),
            ];
        }
        return $out;
    }

    /**
     * Helper method to query nodes using multiple XPath expressions
     * 
     * @param array $xpaths Array of XPath expressions to try
     * @param \DOMNode|null $contextNode Optional context node
     * @param \DOMXPath|null $localXpath Optional local XPath object
     * @return array Array of matched nodes
     */
    private function queryNodesWithContext(array $xpaths, ?\DOMNode $contextNode = null, ?\DOMXPath $localXpath = null): array
    {
        // If local XPath is provided, use it, otherwise use the global one
        $xpathObj = $localXpath ?? $this->xpath;
        
        foreach ($xpaths as $xpath) {
            try {
                $nodes = $xpathObj->query($xpath, $contextNode);
                if ($nodes && $nodes->length > 0) {
                    $result = [];
                    foreach ($nodes as $node) {
                        $result[] = $node;
                    }
                    return $result;
                }
            } catch (\Exception $e) {
                $this->logger->warning("XPath query failed: $xpath - " . $e->getMessage());
            }
        }
        
        return [];
    }
    
    /**
     * Helper method to query nodes using multiple XPath expressions
     * Maintains backward compatibility with existing code
     * 
     * @param array $xpaths Array of XPath expressions to try
     * @param \DOMNode|null $contextNode Optional context node
     * @return array Array of matched nodes
     */
    private function queryNodes(array $xpaths, ?\DOMNode $contextNode = null): array
    {
        return $this->queryNodesWithContext($xpaths, $contextNode);
    }
    
    /**
     * Helper method to query a single node using multiple XPath expressions
     * 
     * @param array $xpaths Array of XPath expressions to try
     * @param \DOMNode|null $contextNode Optional context node
     * @param \DOMXPath|null $localXpath Optional local XPath object
     * @return \DOMNode|null First matched node or null
     */
    private function queryNodeWithContext(array $xpaths, ?\DOMNode $contextNode = null, ?\DOMXPath $localXpath = null): ?\DOMNode
    {
        $nodes = $this->queryNodesWithContext($xpaths, $contextNode, $localXpath);
        return !empty($nodes) ? $nodes[0] : null;
    }
    
    /**
     * Helper method to query a single node using multiple XPath expressions
     * Maintains backward compatibility with existing code
     * 
     * @param array $xpaths Array of XPath expressions to try
     * @param \DOMNode|null $contextNode Optional context node
     * @return \DOMNode|null First matched node or null
     */
    private function queryNode(array $xpaths, ?\DOMNode $contextNode = null): ?\DOMNode
    {
        return $this->queryNodeWithContext($xpaths, $contextNode);
    }

    /**
     * Get node value from multiple XPath expressions
     * 
     * @param array $xpaths Array of XPath expressions to try
     * @param \DOMNode|null $contextNode Optional context node
     * @param \DOMXPath|null $localXpath Optional local XPath object
     * @return string|null Node value or null if not found
     */
    private function getNodeValueWithContext(array $xpaths, ?\DOMNode $contextNode = null, ?\DOMXPath $localXpath = null): ?string
    {
        $node = $this->queryNodeWithContext($xpaths, $contextNode, $localXpath);
        return $node ? trim($node->nodeValue) : null;
    }
    
    /**
     * Get node value from multiple XPath expressions
     * Maintains backward compatibility with existing code
     * 
     * @param array $xpaths Array of XPath expressions to try
     * @param \DOMNode|null $contextNode Optional context node
     * @return string|null Node value or null if not found
     */
    private function getNodeValue(array $xpaths, ?\DOMNode $contextNode = null): ?string
    {
        return $this->getNodeValueWithContext($xpaths, $contextNode);
    }

    /**
     * Format date value to standard format
     * 
     * @param string|null $date Date to format
     * @param string|null $format Format code from list 55
     * @return string|null Formatted date or null
     */
    private function formatDate(?string $date, ?string $format = null): ?string
    {
        if (!$date) {
            return null;
        }
        
        // Handle common date formats
        if ($format === '00' || $format === null) {
            // YYYYMMDD format
            if (preg_match('/^(\d{4})(\d{2})(\d{2})$/', $date, $matches)) {
                return sprintf('%s-%s-%s', $matches[1], $matches[2], $matches[3]);
            }
        }
        
        // Handle date with time
        if ($format === '13' || $format === '14') {
            // YYYYMMDDThhmm or YYYYMMDDThhmmss format
            if (preg_match('/^(\d{4})(\d{2})(\d{2})T(\d{2})(\d{2})(\d{2})?/', $date, $matches)) {
                $time = isset($matches[6]) ? sprintf('%s:%s:%s', $matches[4], $matches[5], $matches[6]) : 
                                            sprintf('%s:%s:00', $matches[4], $matches[5]);
                return sprintf('%s-%s-%s %s', $matches[1], $matches[2], $matches[3], $time);
            }
        }
        
        // Handle other date formats or return original if no pattern matches
        return $date;
    }
    
    /**
     * Parse contributors (authors, editors, etc.)
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parseContributors(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        $contributorNodes = $this->queryNodesWithContext($this->fieldMappings['contributors'], $productNode, $localXpath);
        
        foreach ($contributorNodes as $contributorNode) {
            $contributor = new \ONIXParser\Model\Contributor();
            
            // Get role
            $role = $this->getNodeValueWithContext($this->fieldMappings['contributor_fields']['role'], $contributorNode, $localXpath);
            if ($role) {
                $contributor->setRole($role);
                // Map role code to name if needed
                $roleName = $this->codeMaps['contributor_role'][$role] ?? '';
                $contributor->setRoleName($roleName);
            }
            
            // Get name (try different name formats)
            $name = $this->getNodeValueWithContext($this->fieldMappings['contributor_fields']['name'], $contributorNode, $localXpath);
            if ($name) {
                $contributor->setName($name);
            } else {
                // Try corporate name
                $corporateName = $this->getNodeValueWithContext($this->fieldMappings['contributor_fields']['corporate_name'], $contributorNode, $localXpath);
                if ($corporateName) {
                    $contributor->setCorporateName($corporateName);
                } else {
                    // Try name parts
                    $firstName = $this->getNodeValueWithContext($this->fieldMappings['contributor_fields']['first_name'], $contributorNode, $localXpath);
                    $lastName = $this->getNodeValueWithContext($this->fieldMappings['contributor_fields']['last_name'], $contributorNode, $localXpath);
                    
                    if ($firstName || $lastName) {
                        $contributor->setNameGiven($firstName);
                        $contributor->setNameFamily($lastName);
                    }
                }
            }
            
            // Only add if we have meaningful contributor data
            if ($contributor->getName() || $contributor->getCorporateName()) {
                $product->addContributor($contributor);
            }
        }
    }
    
    /**
     * Parse publisher information
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parsePublisher(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        // Imprint GLN (ImprintIDType 06)
        $imprintGln = $this->getNodeValueWithContext($this->fieldMappings['publisher']['imprint_gln'], $productNode, $localXpath);
        $product->setImprintGln($imprintGln ?: null);

        // Try publisher name first
        $publisherName = $this->getNodeValueWithContext($this->fieldMappings['publisher']['publisher_name'], $productNode, $localXpath);
        if ($publisherName) {
            $product->setPublisherName($publisherName);
        } else {
            // Fallback to imprint name
            $imprintName = $this->getNodeValueWithContext($this->fieldMappings['publisher']['imprint_name'], $productNode, $localXpath);
            if ($imprintName) {
                $product->setPublisherName($imprintName);
            }
        }
    }
    
    /**
     * Parse publication dates
     *
     * @param \DOMNode $productNode
     * @param Product $product
     * @param \DOMXPath|null $localXpath Optional local XPath object
     */
    private function parsePublicationDates(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
    {
        // Every PublishingDate as sent (role, format, date)
        $dates = $this->fieldMappings['publication_dates'];
        foreach ($this->readDateComposites($dates['nodes'], $dates['role'], $productNode, $localXpath) as $d) {
            $product->addPublishingDate($d['role'], $d['format'], $d['date']);
        }

        // Parse publication date (role = '01')
        $publicationDate = $this->getNodeValueWithContext($this->fieldMappings['publication_dates']['publication'], $productNode, $localXpath);
        if ($publicationDate) {
            $product->setPublicationDate($this->formatDate($publicationDate));
        }
        
        // Parse embargo date as availability date (role = '02')
        $embargoDate = $this->getNodeValueWithContext($this->fieldMappings['publication_dates']['embargo'], $productNode, $localXpath);
        if ($embargoDate) {
            $product->setAvailabilityDate($this->formatDate($embargoDate));
        }
        
        // Parse on sale date as announcement date
        $onSaleDate = $this->getNodeValueWithContext($this->fieldMappings['stock']['on_sale_date'], $productNode, $localXpath);
        if ($onSaleDate) {
            $product->setAnnouncementDate($this->formatDate($onSaleDate));
        }
    }
}
