<?php

namespace ONIXParser\Tests;

use ONIXParser\OnixParser;
use PHPUnit\Framework\TestCase;

class CallbackReturnTest extends TestCase
{
    public function testCallbackReturnFalse()
    {
        // Create a parser
        $parser = new OnixParser();
        
        // Create a callback that returns false after processing 5 products
        $processedCount = 0;
        $callback = function($product, $index, $total) use (&$processedCount) {
            $processedCount++;
            return $processedCount < 5; // Return false after 5 products
        };
        
        // Parse a file with more than 5 products
        $options = [
            'callback' => $callback,
            'continue_on_error' => true,
        ];
        
        // Use a test file that has at least 10 products
        $onix = $parser->parseFileStreaming(__DIR__ . '/fixtures/sample-catalog.xml', $options);
        
        // Verify that only 5 products were processed
        $this->assertEquals(5, $processedCount);
        $this->assertLessThanOrEqual(5, count($onix->getProducts()));
    }
}