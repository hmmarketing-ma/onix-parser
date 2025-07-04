<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Set the path to the ONIX file
$onixFilePath = __DIR__ . '/fixtures/onix_samples/451048018.xml';

// Create a logger with INFO level to see important messages
$logger = new Logger(Logger::INFO, 'onix_streaming_test.log');

echo "ONIX Parser Refactoring Test\n";
echo "==========================\n\n";

try {
    // Initialize parser with logger
    echo "Initializing parser...\n";
    $parser = new OnixParser($logger);
    
    // Parse using regular method
    echo "Parsing with regular method...\n";
    $regularOnix = $parser->parseFile($onixFilePath);
    $regularProducts = $regularOnix->getProducts();
    
    // Parse using streaming method
    echo "Parsing with streaming method...\n";
    $streamingOnix = $parser->parseFileStreaming($onixFilePath);
    $streamingProducts = $streamingOnix->getProducts();
    
    // Compare results
    echo "\nComparison Results:\n";
    echo "-------------------\n";
    echo "Regular method products: " . count($regularProducts) . "\n";
    echo "Streaming method products: " . count($streamingProducts) . "\n\n";
    
    // Check if counts match
    if (count($regularProducts) !== count($streamingProducts)) {
        echo "WARNING: Product counts don't match!\n\n";
    }
    
    // Compare a sample of products
    $sampleSize = min(count($regularProducts), count($streamingProducts), 5);
    
    for ($i = 0; $i < $sampleSize; $i++) {
        $regularProduct = $regularProducts[$i];
        $streamingProduct = $streamingProducts[$i];
        
        echo "Product #" . ($i + 1) . " - Record Reference: " . $regularProduct->getRecordReference() . "\n";
        
        // Compare key fields
        compareField("ISBN", $regularProduct->getIsbn(), $streamingProduct->getIsbn());
        compareField("EAN", $regularProduct->getEan(), $streamingProduct->getEan());
        compareField("Title", $regularProduct->getTitleText(), $streamingProduct->getTitleText());
        compareField("Product Form", $regularProduct->getProductForm(), $streamingProduct->getProductForm());
        compareField("Supplier Name", $regularProduct->getSupplierName(), $streamingProduct->getSupplierName());
        compareField("Supplier Role", $regularProduct->getSupplierRole(), $streamingProduct->getSupplierRole());
        compareField("Supplier GLN", $regularProduct->getSupplierGLN(), $streamingProduct->getSupplierGLN());
        
        // Compare collections
        $regularCollections = $regularProduct->getCollections();
        $streamingCollections = $streamingProduct->getCollections();
        compareField("Collections Count", count($regularCollections), count($streamingCollections));
        
        // Compare subjects
        $regularSubjects = $regularProduct->getSubjects();
        $streamingSubjects = $streamingProduct->getSubjects();
        compareField("Subjects Count", count($regularSubjects), count($streamingSubjects));
        
        // Compare descriptions
        $regularDescriptions = $regularProduct->getDescriptions();
        $streamingDescriptions = $streamingProduct->getDescriptions();
        compareField("Descriptions Count", count($regularDescriptions), count($streamingDescriptions));
        
        // Compare images
        $regularImages = $regularProduct->getImages();
        $streamingImages = $streamingProduct->getImages();
        compareField("Images Count", count($regularImages), count($streamingImages));
        
        echo "\n";
    }
    
    echo "Test completed successfully.\n";
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

/**
 * Compare field values and output the result
 * 
 * @param string $fieldName Field name
 * @param mixed $regularValue Value from regular parsing
 * @param mixed $streamingValue Value from streaming parsing
 */
function compareField($fieldName, $regularValue, $streamingValue) {
    echo "  $fieldName: ";
    
    if ($regularValue === $streamingValue) {
        echo "MATCH";
    } else {
        echo "MISMATCH";
        echo "\n    Regular: " . ($regularValue ?? "NULL");
        echo "\n    Streaming: " . ($streamingValue ?? "NULL");
    }
    
    echo "\n";
}
