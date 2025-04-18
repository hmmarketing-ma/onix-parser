<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Set the path to the ONIX file
$onixFilePath = __DIR__ . '/../tests/fixtures/onix_samples/451030558.xml';

// Create a logger with INFO level to see important messages
$logger = new Logger(Logger::INFO, 'onix_supplier_gln.log');

echo "ONIX Supplier GLN Example\n";
echo "========================\n\n";

try {
    // Initialize parser with logger
    echo "Initializing parser...\n";
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
    
    // Parse the ONIX file using streaming approach
    echo "Parsing ONIX file: $onixFilePath\n\n";
    
    // Options for batch processing
    $options = [
        'limit' => 0,                // 0 means no limit
        'offset' => 0,               // Start from the first product
        'callback' => $productCallback,  // Process each product as it's parsed
        'continue_on_error' => true, // Continue processing if an error occurs
    ];
    
    // Start timer
    $startTime = microtime(true);
    
    // Parse the file
    $onix = $parser->parseFileStreaming($onixFilePath, $options);
    
    // End timer
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    // Display performance information
    echo "\nPerformance Information:\n";
    echo "------------------------\n";
    echo "Execution Time: $executionTime seconds\n";
    echo "Peak Memory Usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
    
    echo "\nONIX Supplier GLN Example Completed Successfully\n";
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
