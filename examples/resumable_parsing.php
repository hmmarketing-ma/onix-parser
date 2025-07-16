<?php

require_once '../vendor/autoload.php';

use ONIXParser\ResumableOnixParser;
use ONIXParser\Resume\CheckpointManager;
use ONIXParser\Logger;

// Example: Resumable ONIX Parser with True File Position Resume
echo "Resumable ONIX Parser Example\n";
echo "=============================\n\n";

// Configuration
$onixFilePath = '../tests/fixtures/onix_samples/451049077.xml';
$checkpointDir = './checkpoints';
$logFile = 'resumable_parser.log';

try {
    // Create logger
    $logger = new Logger(Logger::INFO, $logFile);
    
    // Create checkpoint manager
    $checkpointManager = new CheckpointManager(
        $checkpointDir,
        $logger,
        86400, // 24 hours max age
        5,     // Keep max 5 checkpoints
        false  // No compression
    );
    
    // Create resumable parser
    $parser = new ResumableOnixParser($logger, $checkpointManager);
    
    // Define callback for processing products
    $productCallback = function($product, $index, $total) {
        echo "Processing product " . ($index + 1) . " of $total: " . $product->getRecordReference() . "\n";
        
        if ($product->getTitle()) {
            echo "  Title: " . $product->getTitle()->getText() . "\n";
        }
        
        echo "  Memory: " . round(memory_get_usage() / 1024 / 1024, 2) . " MB\n";
        
        // Simulate processing that might fail or need to be interrupted
        // For demo purposes, we'll stop after 50 products to show resume capability
        if ($index >= 49) { // Stop after 50 products (0-indexed)
            echo "  Stopping after 50 products to demonstrate resume capability...\n";
            return false; // This will create a checkpoint and stop processing
        }
        
        return true;
    };
    
    // Parse with checkpoints enabled
    echo "Starting parsing with checkpoints enabled...\n";
    $options = [
        'enable_checkpoints' => true,
        'checkpoint_interval' => 10,  // Create checkpoint every 10 products
        'auto_resume' => true,        // Automatically resume if checkpoint exists
        'callback' => $productCallback,
        'continue_on_error' => true,
    ];
    
    $startTime = microtime(true);
    
    // First run - will process 50 products then stop
    echo "\n=== First Run ===\n";
    $onix = $parser->parseFileStreaming($onixFilePath, $options);
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    echo "\nFirst run completed in {$executionTime} seconds\n";
    echo "Products processed: " . count($onix->getProducts()) . "\n";
    
    // Show parser statistics
    $stats = $parser->getStats();
    echo "\nParser Statistics:\n";
    echo "- Session products: " . $stats['session_products'] . "\n";
    echo "- Last checkpoint: " . $stats['last_checkpoint_count'] . "\n";
    echo "- Checkpoints enabled: " . ($stats['checkpoints_enabled'] ? 'Yes' : 'No') . "\n";
    
    // Show checkpoint information
    $checkpoints = $checkpointManager->listCheckpoints();
    echo "\nAvailable checkpoints: " . count($checkpoints) . "\n";
    foreach ($checkpoints as $checkpoint) {
        echo "- " . $checkpoint['id'] . " (created: " . date('Y-m-d H:i:s', $checkpoint['created_at']) . ")\n";
    }
    
    echo "\n=== Second Run (Resume) ===\n";
    echo "Now resuming from checkpoint...\n";
    
    // Modify callback to continue from where we left off
    $resumeCallback = function($product, $index, $total) {
        echo "Resuming product " . ($index + 1) . " of $total: " . $product->getRecordReference() . "\n";
        
        if ($product->getTitle()) {
            echo "  Title: " . $product->getTitle()->getText() . "\n";
        }
        
        return true; // Continue processing all remaining products
    };
    
    $resumeOptions = [
        'enable_checkpoints' => true,
        'checkpoint_interval' => 25,  // Create checkpoint every 25 products
        'auto_resume' => true,        // This will automatically find and resume from checkpoint
        'callback' => $resumeCallback,
        'continue_on_error' => true,
    ];
    
    $startTime = microtime(true);
    
    // Create new parser instance for resume (simulating restart)
    $resumeParser = new ResumableOnixParser($logger, $checkpointManager);
    $resumedOnix = $resumeParser->parseFileStreaming($onixFilePath, $resumeOptions);
    
    $endTime = microtime(true);
    $executionTime = round($endTime - $startTime, 2);
    
    echo "\nSecond run (resume) completed in {$executionTime} seconds\n";
    echo "Total products processed: " . count($resumedOnix->getProducts()) . "\n";
    
    // Show final statistics
    $finalStats = $resumeParser->getStats();
    echo "\nFinal Statistics:\n";
    echo "- Session products: " . $finalStats['session_products'] . "\n";
    echo "- Was resuming: " . ($finalStats['is_resuming'] ? 'Yes' : 'No') . "\n";
    
    // Cleanup old checkpoints
    echo "\nCleaning up old checkpoints...\n";
    $cleanedUp = $checkpointManager->cleanupOldCheckpoints();
    echo "Cleaned up: $cleanedUp checkpoints\n";
    
    echo "\n=== Performance Comparison ===\n";
    echo "This example demonstrates:\n";
    echo "1. Automatic checkpoint creation during parsing\n";
    echo "2. Interruption handling (stopped after 50 products)\n";
    echo "3. Automatic resume from exact byte position\n";
    echo "4. No duplicate processing of products\n";
    echo "5. Memory efficiency maintained throughout\n";
    
    echo "\nFor large files (48,000+ products), you can now:\n";
    echo "- Process in batches with regular checkpoints\n";
    echo "- Survive server restarts and timeouts\n";
    echo "- Resume from exact byte position\n";
    echo "- Maintain constant memory usage\n";
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    // Show checkpoint information even on error
    if (isset($checkpointManager)) {
        $checkpoints = $checkpointManager->listCheckpoints();
        echo "\nCheckpoints available for resume: " . count($checkpoints) . "\n";
    }
}