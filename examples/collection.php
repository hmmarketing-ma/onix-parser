<?php

require_once 'vendor/autoload.php';

use ONIXParser\OnixParser;

// Create a parser
$parser = new OnixParser();

try {
    // Parse an ONIX file
    $onix = $parser->parseFile( 'tests/fixtures/onix_samples/Sample_ONIX_3.0.xml');
    
    // Access product information
    foreach ($onix->getProducts() as $product) {
        echo "ISBN: " . $product->getIsbn() . "\n";
        echo "Title: " . $product->getTitle()->getText() . "\n";
        
        // Check if product is part of a series or collection
        if ($product->isPartOfSeries() || $product->isPartOfCollection()) {
            echo "Collections/Series:\n";
            
            foreach ($product->getCollections() as $collection) {
                echo "- Type: " . $collection->getTypeName() . "\n";
                echo "  Main Title: " . $collection->getTitleText() . "\n";
                
                if ($collection->getPartNumber()) {
                    echo "  Part Number: " . $collection->getPartNumber() . "\n";
                }
                
                // Display additional titles
                $additionalTitles = $collection->getAdditionalTitles();
                if (!empty($additionalTitles)) {
                    echo "  Additional Titles:\n";
                    
                    foreach ($additionalTitles as $type => $levelTitles) {
                        $typeName = match ($type) {
                            '01' => 'Distinctive title',
                            '05' => 'Abbreviated title',
                            '10' => 'Alternative title',
                            default => "Type $type"
                        };
                        
                        foreach ($levelTitles as $level => $text) {
                            $levelName = match ($level) {
                                '01' => 'Product level',
                                '02' => 'Collection level',
                                '03' => 'Subcollection level',
                                default => "Level $level"
                            };
                            
                            echo "    - $typeName ($levelName): $text\n";
                        }
                    }
                }
                
                echo "\n";
            }
        } else {
            echo "Not part of any collection or series\n";
        }
        
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}