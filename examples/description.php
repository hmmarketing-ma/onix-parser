<?php
require_once 'vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Create a parser with a custom logger for debugging
$logger = new Logger(Logger::DEBUG);
$parser = new OnixParser($logger);

try {
    // Parse an ONIX file
    $onix = $parser->parseFile( 'tests/fixtures/onix_samples/Sample_ONIX_3.0.xml');
    
    // Process products
    foreach ($onix->getProducts() as $product) {
        echo "=============================================\n";
        echo "Product: " . $product->getRecordReference() . "\n";
        echo "Title: " . ($product->getTitle() ? $product->getTitle()->getText() : 'N/A') . "\n";
        // Display identifiers
        if ($product->getIsbn()) echo "ISBN: " . $product->getIsbn() . "\n";
        if ($product->getEan()) echo "EAN: " . $product->getEan() . "\n";
        echo "=============================================\n\n";
        
        // Get all descriptions
        $descriptions = $product->getDescriptions();
        echo "Found " . count($descriptions) . " descriptions\n\n";
        
        // Main Description
        $mainDesc = $product->getMainDescription();
        if ($mainDesc) {
            echo "MAIN DESCRIPTION:\n";
            echo "----------------\n";
            echo $mainDesc->getExcerpt(300) . "\n\n";
        }
        
        // Short Description
        $shortDesc = $product->getShortDescription();
        if ($shortDesc) {
            echo "SHORT DESCRIPTION:\n";
            echo "-----------------\n";
            echo $shortDesc->getPlainText() . "\n\n";
        }
        
        // Long Description (if different from main)
        $longDesc = $product->getLongDescription();
        if ($longDesc && $longDesc !== $mainDesc) {
            echo "LONG DESCRIPTION (excerpt):\n";
            echo "-------------------------\n";
            echo $longDesc->getExcerpt(200) . "\n\n";
        }
        
        // Table of Contents
        $toc = $product->getTableOfContents();
        if ($toc) {
            echo "TABLE OF CONTENTS (excerpt):\n";
            echo "---------------------------\n";
            echo $toc->getExcerpt(200) . "\n\n";
        }
        
        // Review Quotes
        $reviews = $product->getReviewQuotes();
        if (!empty($reviews)) {
            echo "REVIEW QUOTES:\n";
            echo "-------------\n";
            foreach ($reviews as $i => $review) {
                echo ($i+1) . ". " . $review->getPlainText() . "\n";
            }
            echo "\n";
        }
        
        // Show description formats
        echo "DESCRIPTION FORMATS:\n";
        echo "------------------\n";
        foreach ($descriptions as $desc) {
            echo "- " . $desc->getTypeName() . ": ";
            echo ($desc->isHtml() ? "HTML Format" : "Plain Text Format") . "\n";
        }
        echo "\n\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}