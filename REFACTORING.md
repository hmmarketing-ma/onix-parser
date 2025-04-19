# ONIX Parser Refactoring

## Overview

This document outlines the refactoring performed on the ONIX Parser library to ensure consistent behavior between the regular parsing method (`parseProduct`) and the streaming parsing method (`parseProductStreaming`).

## Problem Statement

The original implementation had the following issues:

1. The streaming method (`parseProductStreaming`) was a simplified version of the regular method and didn't extract all the same data.
2. Specifically, `$product->getSupplierGLN()` returned null when using the streaming method, even when the GLN was present in the XML.
3. This inconsistency caused problems when using the streaming method for large files, as some data was missing.
4. Code duplication between the two methods made maintenance difficult.
5. Users had to choose between memory efficiency (streaming) and complete data extraction (regular), which was not ideal.

## Solution

The refactoring approach focused on:

1. Creating a common parsing framework that can be used by both regular and streaming methods.
2. Extracting shared parsing logic into helper methods that can work with either a global XPath context or a node-specific one.
3. Ensuring the streaming method extracts all the same data as the regular method, particularly the supplier GLN.
4. Maintaining backward compatibility with existing code.
5. Preserving the memory efficiency of the streaming method.

## Key Changes

### 1. Context-Aware Helper Methods

Created new helper methods that can work with either a global or local XPath context:

- `getNodeValueWithContext`
- `queryNodesWithContext`
- `queryNodeWithContext`

These methods accept an optional `$localXpath` parameter, which allows them to be used with either the global XPath object or a local one.

Example implementation:

```php
private function getNodeValueWithContext(array $xpaths, ?\DOMNode $contextNode = null, ?\DOMXPath $localXpath = null): ?string
{
    $xpath = $localXpath ?? $this->xpath;
    
    foreach ($xpaths as $xpathExpr) {
        $result = $xpath->evaluate($xpathExpr, $contextNode ?? $this->dom);
        
        if ($result instanceof \DOMNodeList && $result->length > 0) {
            return $result->item(0)->nodeValue;
        } elseif (is_string($result) && !empty($result)) {
            return $result;
        }
    }
    
    return null;
}
```

### 2. Updated Parsing Methods

Modified all parsing methods to accept and use an optional local XPath context:

- `parseNotification`
- `parseIdentifiers`
- `parseTitle`
- `parseProductForm`
- `parseSubjects`
- `parseDescriptions`
- `parseImages`
- `parseCollections`
- `parseSupply`

Each method now has a `$localXpath` parameter that can be passed to the context-aware helper methods:

```php
private function parseSupply(\DOMNode $productNode, Product $product, ?\DOMXPath $localXpath = null): void
{
    $xpath = $localXpath ?? $this->xpath;
    $supplyNodes = $this->queryNodesWithContext($this->fieldMappings['supply_detail'], $productNode, $xpath);
    
    if (!$supplyNodes || $supplyNodes->length === 0) {
        return;
    }
    
    // Process each supply detail
    foreach ($supplyNodes as $supplyNode) {
        // Extract supplier information
        $supplierName = $this->getNodeValueWithContext($this->fieldMappings['supplier_name'], $supplyNode, $xpath);
        $supplierRole = $this->getNodeValueWithContext($this->fieldMappings['supplier_role'], $supplyNode, $xpath);
        $supplierGLN = $this->getNodeValueWithContext($this->fieldMappings['supplier_gln'], $supplyNode, $xpath);
        
        // Set supplier information on the product
        $product->setSupplierName($supplierName);
        $product->setSupplierRole($supplierRole);
        $product->setSupplierGLN($supplierGLN);
        
        // Extract availability information
        // ...
    }
}
```

### 3. Refactored Streaming Method

Completely refactored the `parseProductStreaming` method to use the same parsing methods as the regular method, but with a local XPath context:

```php
private function parseProductStreaming(\DOMNode $productNode): Product
{
    $product = new Product();
    
    // Create a new DOM document and import the product node
    $dom = new \DOMDocument();
    $importedNode = $dom->importNode($productNode, true);
    $dom->appendChild($importedNode);
    
    // Create a new XPath object for this document
    $xpath = new \DOMXPath($dom);
    
    // Register namespace if needed
    if ($this->hasNamespace) {
        $xpath->registerNamespace('onix', $this->namespaceURI);
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
    
    // Parse supply details (availability, prices)
    $this->parseSupply($importedNode, $product, $xpath);
    
    // Store original XML
    $productXml = new \SimpleXMLElement($dom->saveXML($importedNode));
    $product->setXml($productXml);
    
    return $product;
}
```

### 4. Backward Compatibility

Maintained backward compatibility by keeping the original methods and having them call the new context-aware methods:

```php
private function getNodeValue(array $xpaths, ?\DOMNode $contextNode = null): ?string
{
    return $this->getNodeValueWithContext($xpaths, $contextNode);
}
```

### 5. Enhanced Supplier Information Extraction

Special attention was given to the extraction of supplier information, particularly the Global Location Number (GLN), which was previously missing in the streaming method:

```php
// In parseSupply method
$supplierGLN = $this->getNodeValueWithContext($this->fieldMappings['supplier_gln'], $supplyNode, $xpath);
$product->setSupplierGLN($supplierGLN);
```

## Testing

A comprehensive test script (`tests/streaming_test.php`) was created to verify that the refactoring works correctly. The script:

1. Parses the same ONIX file using both the regular and streaming methods.
2. Compares the results to ensure that both methods extract the same data.
3. Specifically checks that the supplier GLN is correctly extracted in both methods.
4. Compares other important fields like ISBN, EAN, title, product form, supplier name, and supplier role.
5. Verifies that collections, subjects, descriptions, and images are correctly extracted in both methods.

Example test output:

```
ONIX Parser Refactoring Test
==========================

Initializing parser...
Parsing with regular method...
Parsing with streaming method...

Comparison Results:
-------------------
Regular method products: 5
Streaming method products: 5

Product #1 - Record Reference: 451030558_1
  ISBN: MATCH
  EAN: MATCH
  Title: MATCH
  Product Form: MATCH
  Supplier Name: MATCH
  Supplier Role: MATCH
  Supplier GLN: MATCH
  Collections Count: MATCH
  Subjects Count: MATCH
  Descriptions Count: MATCH
  Images Count: MATCH

Product #2 - Record Reference: 451030558_2
  ...
```

## Benefits

1. **Consistent Data Extraction**: Both methods now extract the same data, including the supplier GLN.
2. **Reduced Code Duplication**: Common parsing logic is extracted into shared methods.
3. **Maintainability**: Changes to parsing logic only need to be made in one place.
4. **Memory Efficiency**: The streaming method maintains its memory efficiency by processing one product at a time.
5. **Backward Compatibility**: The refactoring preserves the existing API, ensuring backward compatibility.
6. **Enhanced Supplier Information**: Complete supplier information, including GLN, is now available in both methods.
7. **Improved Developer Experience**: Developers can now use the streaming method for large files without sacrificing data completeness.

## Example Usage

The following example demonstrates how to use the streaming method to extract supplier information, including the GLN:

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Set the path to the ONIX file
$onixFilePath = __DIR__ . '/../tests/fixtures/onix_samples/451030558.xml';

// Create a logger with INFO level
$logger = new Logger(Logger::INFO, 'onix_supplier_gln.log');

// Initialize parser with logger
$parser = new OnixParser($logger);

// Define a callback function to process each product as it's parsed
$productCallback = function($product, $index, $total) {
    echo "Processing product " . ($index + 1) . " of $total: " . $product->getRecordReference() . "\n";
    
    // Display supplier information
    echo "  Supplier Name: " . ($product->getSupplierName() ?: 'N/A') . "\n";
    echo "  Supplier Role: " . ($product->getSupplierRole() ?: 'N/A') . "\n";
    echo "  Supplier GLN: " . ($product->getSupplierGLN() ?: 'N/A') . "\n";
    
    // Print memory usage
    echo "  Memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n\n";
};

// Options for batch processing
$options = [
    'limit' => 0,                // 0 means no limit
    'offset' => 0,               // Start from the first product
    'callback' => $productCallback,  // Process each product as it's parsed
    'continue_on_error' => true, // Continue processing if an error occurs
];

// Parse the file using streaming approach
$onix = $parser->parseFileStreaming($onixFilePath, $options);
```

## Future Improvements

1. **Unit Tests**: Add more comprehensive unit tests to verify the behavior of both parsing methods.
2. **Performance Optimization**: Further optimize the streaming method for large files.
3. **Documentation**: Improve documentation to make it clear that both methods extract the same data.
4. **Additional ONIX Elements**: Extend the parser to support more ONIX elements and attributes.
5. **Caching**: Implement caching mechanisms to improve performance for frequently accessed data.
6. **Error Handling**: Enhance error handling and reporting for better debugging.
7. **Validation**: Add validation of ONIX data against the official schema.
