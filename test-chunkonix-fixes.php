<?php
/**
 * Standalone Test for ChunkOnixParser Fixes
 * 
 * Tests the fixed ChunkOnixParser to verify it can parse all products 
 * in the 451048018.xml file without stopping early at 39,504
 */

require_once __DIR__ . '/vendor/autoload.php';

// Remove execution time limit for large file processing
set_time_limit(0); // 0 = no time limit
// Note: Memory limit not increased - ChunkOnixParser uses streaming so memory usage should stay low

use ONIXParser\ChunkOnixParser;
use ONIXParser\OnixParser;
use ONIXParser\Logger as OnixLogger;

echo "🧪 CHUNKONIX PARSER FIXES TEST\n";
echo "==============================\n\n";

// Use the test file from dilicom-wc project
$filePath = __DIR__ . '/tests/fixtures/onix_samples/451048018.xml';
$fileSize = filesize($filePath);

if (!file_exists($filePath)) {
    echo "❌ ERROR: Test file not found: $filePath\n";
    echo "Please ensure the ONIX test file exists in the dilicom-wc project.\n";
    exit(1);
}

echo "📊 Test File Information:\n";
echo "   Path: $filePath\n";
echo "   Size: " . number_format($fileSize) . " bytes (" . round($fileSize / 1024 / 1024, 2) . " MB)\n";
echo "   Expected: Should parse MORE than 39,504 products (previous failure point)\n";
echo "   Target: Should reach closer to 79,000 total products\n\n";

try {
    // Create ONIX logger and parser
    $onixLogger = new OnixLogger();
    $onixParser = new OnixParser($onixLogger);
    
    // Test 1: Count total products with fixed getProductCount method
    echo "🔢 Test 1: Product Counting with Fixed Method\n";
    echo "=============================================\n";
    
    $chunkParser = new ChunkOnixParser($filePath, $onixParser, $onixLogger);
    $chunkParser->setChunkSize(1024 * 1024); // 1MB chunks (same as failed test)
    
    $countStartTime = microtime(true);
    $totalProductCount = $chunkParser->getProductCount();
    $countDuration = round(microtime(true) - $countStartTime, 2);
    
    echo "   ✅ Product counting: $totalProductCount products in {$countDuration}s\n";
    echo "   📊 This gives us the baseline for comparison\n\n";
    
    // Test 2: Parse with unlimited processing using fixed buffer management
    echo "🚀 Test 2: Full Parsing with Fixed Buffer Management\n";
    echo "===================================================\n";
    echo "   Previous result: 39,504 products (stopped prematurely)\n";
    echo "   Testing fixes: Safe buffer trimming + improved EOF detection\n\n";
    
    // Clear any existing checkpoint to start fresh
    $chunkParser->clearCheckpoint();
    
    $parsedProducts = 0;
    $parseStartTime = microtime(true);
    $lastProgressTime = $parseStartTime;
    $milestones = [10000, 20000, 30000, 39504, 40000, 50000, 58673, 60000, 70000, 79000];
    $milestonePassed = [];
    
    $result = $chunkParser->parseWithCheckpoints(function($product, $productNumber) use (&$parsedProducts, &$lastProgressTime, $milestones, &$milestonePassed) {
        $parsedProducts++;
        
        // Check milestones
        foreach ($milestones as $milestone) {
            if ($parsedProducts == $milestone && !in_array($milestone, $milestonePassed)) {
                $currentTime = microtime(true);
                $elapsed = round($currentTime - $GLOBALS['parseStartTime'], 2);
                
                if ($milestone == 39504) {
                    echo "   🎯 MILESTONE: Reached 39,504 products (previous failure point) in {$elapsed}s\n";
                    echo "      ✅ Successfully passed the previous stopping point!\n";
                } elseif ($milestone == 58673) {
                    echo "   🎯 MILESTONE: Reached 58,673 products (previous best) in {$elapsed}s\n";
                    echo "      🎉 Exceeded the previous streaming parser result!\n";
                } else {
                    echo "   🎯 MILESTONE: Reached " . number_format($milestone) . " products in {$elapsed}s\n";
                }
                
                $milestonePassed[] = $milestone;
            }
        }
        
        // Progress updates every 5,000 products
        if ($parsedProducts % 5000 === 0) {
            $currentTime = microtime(true);
            $intervalTime = $currentTime - $lastProgressTime;
            $rate = 5000 / $intervalTime;
            
            echo "   📍 Progress: " . number_format($parsedProducts) . " products (Rate: " . round($rate) . "/sec)\n";
            $lastProgressTime = $currentTime;
        }
        
        return true; // Continue parsing - don't stop early
    }, 1000);
    
    $GLOBALS['parseStartTime'] = $parseStartTime; // For milestone tracking
    
    $parseDuration = round(microtime(true) - $parseStartTime, 2);
    
    echo "\n📋 Final Parsing Results:\n";
    echo "========================\n";
    echo "   Products parsed: " . number_format($parsedProducts) . "\n";
    echo "   Total parsing time: {$parseDuration}s\n";
    echo "   Average rate: " . round($parsedProducts / max($parseDuration, 1)) . " products/sec\n";
    
    // Get final checkpoint info
    $finalCheckpoint = $chunkParser->getCheckpointInfo();
    if ($finalCheckpoint) {
        $finalPercent = round(($finalCheckpoint['position'] / $fileSize) * 100, 2);
        echo "   Final file position: " . number_format($finalCheckpoint['position']) . " bytes ({$finalPercent}%)\n";
        $remainingBytes = $fileSize - $finalCheckpoint['position'];
        echo "   Remaining bytes: " . number_format($remainingBytes) . "\n";
        
        if ($remainingBytes > 0) {
            echo "   ⚠️ Parsing stopped before reaching end of file\n";
        } else {
            echo "   ✅ Reached end of file successfully\n";
        }
    } else {
        echo "   ✅ No checkpoint (parsing completed and cleared)\n";
    }
    
    echo "\n🎯 RESULTS ANALYSIS:\n";
    echo "==================\n";
    
    $improvement = $parsedProducts - 39504;
    $previousBest = 58673;
    
    if ($parsedProducts <= 39504) {
        echo "   ❌ FAILED: Still stopping at same point (≤39,504 products)\n";
        echo "   🔍 The fixes did not resolve the buffer management issue\n";
        echo "   📝 More investigation needed in ChunkOnixParser logic\n";
    } elseif ($parsedProducts > 39504 && $parsedProducts < $previousBest) {
        echo "   🔄 PARTIAL SUCCESS: Improved by " . number_format($improvement) . " products\n";
        echo "   📈 Progress made, but still below previous best of " . number_format($previousBest) . "\n";
        echo "   🔧 Buffer fixes helped, but more optimization needed\n";
    } elseif ($parsedProducts >= $previousBest && $parsedProducts < $totalProductCount * 0.95) {
        echo "   👍 GOOD PROGRESS: Exceeded previous best of " . number_format($previousBest) . "\n";
        echo "   📈 Improvement: +" . number_format($parsedProducts - $previousBest) . " products over previous best\n";
        echo "   🔧 Buffer fixes working, but may still have room for improvement\n";
    } elseif ($parsedProducts >= $totalProductCount * 0.95) {
        echo "   🎉 EXCELLENT SUCCESS: Parsed ≥95% of total products!\n";
        echo "   ✅ The buffer management fixes have resolved the issue\n";
        echo "   🚀 Ready for production use with large ONIX files\n";
    }
    
    echo "\n📊 Comparison Summary:\n";
    echo "   Original ChunkParser: 39,504 products ❌\n";
    echo "   Previous streaming: " . number_format($previousBest) . " products 🔄\n";
    echo "   Fixed ChunkParser: " . number_format($parsedProducts) . " products ";
    
    if ($parsedProducts > $previousBest) {
        echo "🎉\n";
    } elseif ($parsedProducts > 39504) {
        echo "📈\n";
    } else {
        echo "❌\n";
    }
    
    // Test 3: Analyze remaining content if parsing stopped early
    if ($finalCheckpoint && $finalCheckpoint['position'] < $fileSize) {
        echo "\n🔍 Test 3: Remaining Content Analysis\n";
        echo "====================================\n";
        
        $remainingSize = $fileSize - $finalCheckpoint['position'];
        echo "   Analyzing " . number_format($remainingSize) . " remaining bytes...\n";
        
        // Sample the remaining content to see if there are more products
        $handle = fopen($filePath, 'r');
        if ($handle) {
            fseek($handle, $finalCheckpoint['position']);
            $sampleSize = min(50000, $remainingSize); // Sample up to 50KB
            $nextContent = fread($handle, $sampleSize);
            fclose($handle);
            
            // Count Product tags in remaining content
            $remainingProductMatches = preg_match_all('/<Product[>\s]/', $nextContent);
            echo "   <Product> tags in next " . number_format($sampleSize) . " bytes: $remainingProductMatches\n";
            
            if ($remainingProductMatches > 0) {
                echo "   ⚠️ More products found - parsing stopped prematurely\n";
                echo "   🔧 May need additional fixes in ChunkOnixParser\n";
            } else {
                echo "   ✅ No additional <Product> tags found in sample\n";
                echo "   🎯 Parser may have reached actual end of products\n";
            }
            
            // Show first 200 characters of remaining content
            echo "   First 200 chars of remaining content:\n";
            echo "   " . substr($nextContent, 0, 200) . "...\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n🎯 CONCLUSION:\n";
echo "==============\n";
echo "✅ If parsed products > 39,504: Buffer management fixes are working\n";
echo "🎉 If parsed products ≥ 58,673: Fixes exceed previous best result\n";
echo "🚀 If parsed products ≥ 75,000: Ready to push fixes to GitHub\n";
echo "❌ If parsed products ≤ 39,504: More investigation needed\n\n";

echo "Test completed at " . date('Y-m-d H:i:s') . "\n";