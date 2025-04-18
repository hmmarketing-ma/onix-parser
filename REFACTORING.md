# ONIX Parser Refactoring

## Overview

This document outlines the refactoring performed on the ONIX Parser library to ensure consistent behavior between the regular parsing method (`parseProduct`) and the streaming parsing method (`parseProductStreaming`).

## Problem Statement

The original implementation had the following issues:

1. The streaming method (`parseProductStreaming`) was a simplified version of the regular method and didn't extract all the same data.
2. Specifically, `$product->getSupplierGLN()` returned null when using the streaming method, even when the GLN was present in the XML.
3. This inconsistency caused problems when using the streaming method for large files, as some data was missing.
4. Code duplication between the two methods made maintenance difficult.

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

Each method now has a `$localXpath` parameter that can be passed to the context-aware helper methods.

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

## Testing

A test script (`tests/streaming_test.php`) was created to verify that the refactoring works correctly. The script:

1. Parses the same ONIX file using both the regular and streaming methods.
2. Compares the results to ensure that both methods extract the same data.
3. Specifically checks that the supplier GLN is correctly extracted in both methods.

## Benefits

1. **Consistent Data Extraction**: Both methods now extract the same data, including the supplier GLN.
2. **Reduced Code Duplication**: Common parsing logic is extracted into shared methods.
3. **Maintainability**: Changes to parsing logic only need to be made in one place.
4. **Memory Efficiency**: The streaming method maintains its memory efficiency by processing one product at a time.
5. **Backward Compatibility**: The refactoring preserves the existing API, ensuring backward compatibility.

## Future Improvements

1. **Unit Tests**: Add more comprehensive unit tests to verify the behavior of both parsing methods.
2. **Performance Optimization**: Further optimize the streaming method for large files.
3. **Documentation**: Improve documentation to make it clear that both methods extract the same data.
