<?php

require_once '../vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Set the path to the ONIX file
$onixFilePath = '../tests/fixtures/onix_samples/451049077.xml';

// Create a logger with INFO level to see important messages
$logger = new Logger(Logger::INFO, 'onix_streaming_parser.log');

echo "ONIX Streaming Parser Example\n";
echo "============================\n\n";

try {
    // Initialize parser with logger
    echo "Initializing parser...\n";
    $parser = new OnixParser($logger);
    
    // Define a callback function to process each product as it's parsed
    $productCallback = function($product, $index, $total) {
        echo "Processing product " . ($index + 1) . " of $total: " . $product->getRecordReference() . "\n";
        
        // You can do additional processing here, such as:
        // - Save to database
        // - Generate reports
        // - Export to other formats
        
        // For this example, we'll just print the title
        if ($product->getTitle()) {
            echo "  Title: " . $product->getTitle()->getText() . "\n";
        }
        
        // Print memory usage
        echo "  Memory usage: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
    };
    
    // Parse the ONIX file using streaming approach
    echo "Parsing ONIX file: $onixFilePath\n";
    
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
    
    // Display header information
    $header = $onix->getHeader();
    echo "\nONIX Header Information:\n";
    echo "------------------------\n";
    echo "ONIX Version: " . $onix->getVersion() . "\n";
    echo "Sender: " . $header->getSender() . "\n";
    if ($header->getContact()) echo "Contact: " . $header->getContact() . "\n";
    if ($header->getEmail()) echo "Email: " . $header->getEmail() . "\n";
    echo "Sent Date: " . $header->getSentDateTime() . "\n";
    echo "Total Products: " . count($onix->getProducts()) . "\n";
    echo "Available Products: " . count($onix->getProductsAvailable()) . "\n";
    
    // Display performance information
    echo "\nPerformance Information:\n";
    echo "------------------------\n";
    echo "Execution Time: $executionTime seconds\n";
    echo "Peak Memory Usage: " . round(memory_get_peak_usage() / 1024 / 1024, 2) . " MB\n";
    
    echo "\nONIX Streaming Parser Example Completed Successfully\n";
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
