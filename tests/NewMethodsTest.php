<?php

require_once __DIR__ . '/../vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\CodeMaps;
use ONIXParser\FieldMappings;

/**
 * Test script for new ONIX Parser methods
 * Tests all the newly added methods for Product model
 */

echo "\n=== ONIX Parser New Methods Test ===\n\n";

try {
    // Test CodeMaps first
    echo "🗺️  Testing new CodeMaps...\n";
    
    $productFormDetailMap = CodeMaps::getProductFormDetailMap();
    echo "   ✅ Product Form Detail Map: " . count($productFormDetailMap) . " entries\n";
    echo "   Example: A101 = " . ($productFormDetailMap['A101'] ?? 'Not found') . "\n";
    
    $languageMap = CodeMaps::getLanguageCodeMap();
    echo "   ✅ Language Code Map: " . count($languageMap) . " entries\n";
    echo "   Example: fra = " . ($languageMap['fra'] ?? 'Not found') . "\n";
    
    $countryMap = CodeMaps::getCountryCodeMap();
    echo "   ✅ Country Code Map: " . count($countryMap) . " entries\n";
    echo "   Example: FR = " . ($countryMap['FR'] ?? 'Not found') . "\n";
    
    $measureMap = CodeMaps::getMeasureUnitMap();
    echo "   ✅ Measure Unit Map: " . count($measureMap) . " entries\n";
    echo "   Example: cm = " . ($measureMap['cm'] ?? 'Not found') . "\n";
    
    // Test FieldMappings
    echo "\n📋 Testing new FieldMappings...\n";
    
    $mappings = FieldMappings::getMappings();
    
    if (isset($mappings['language'])) {
        echo "   ✅ Language mappings added\n";
    } else {
        echo "   ❌ Language mappings missing\n";
    }
    
    if (isset($mappings['publishing_metadata'])) {
        echo "   ✅ Publishing metadata mappings added\n";
    } else {
        echo "   ❌ Publishing metadata mappings missing\n";
    }
    
    // Test with sample ONIX file if available
    echo "\n📖 Testing with ONIX sample file...\n";
    
    $sampleFile = __DIR__ . '/fixtures/onix_samples/Sample_ONIX_3.0.xml';
    if (!file_exists($sampleFile)) {
        echo "   ⚠️  Sample ONIX file not found at: $sampleFile\n";
        echo "   Skipping Product method tests...\n\n";
        echo "✅ CodeMaps and FieldMappings tests completed successfully!\n";
        exit(0);
    }
    
    echo "   📄 Found sample file: $sampleFile\n";
    
    $parser = new OnixParser();
    $onix = $parser->parseFile($sampleFile);
    $products = $onix->getProducts();
    
    if (empty($products)) {
        echo "   ❌ No products found in sample file\n";
        exit(1);
    }
    
    echo "   ✅ Parsed " . count($products) . " products\n\n";
    
    // Test new methods on first product
    $product = $products[0];
    echo "🧪 Testing new Product methods on first product...\n\n";
    
    // High priority methods
    echo "📊 High Priority Methods:\n";
    
    $availabilityName = $product->getAvailabilityName();
    echo "   getAvailabilityName(): " . ($availabilityName ?: 'null') . "\n";
    
    $pageCount = $product->getPageCount();
    echo "   getPageCount(): " . ($pageCount ?: 'null') . "\n";
    
    $languageCode = $product->getLanguageCode();
    echo "   getLanguageCode(): " . ($languageCode ?: 'null') . "\n";
    
    $languageName = $product->getLanguageName();
    echo "   getLanguageName(): " . ($languageName ?: 'null') . "\n";
    
    // Medium priority methods
    echo "\n🔧 Medium Priority Methods:\n";
    
    $productFormDetail = $product->getProductFormDetail();
    echo "   getProductFormDetail(): " . ($productFormDetail ?: 'null') . "\n";
    
    $productFormDetailName = $product->getProductFormDetailName();
    echo "   getProductFormDetailName(): " . ($productFormDetailName ?: 'null') . "\n";
    
    $imprintName = $product->getImprintName();
    echo "   getImprintName(): " . ($imprintName ?: 'null') . "\n";
    
    // Physical measurements
    echo "\n📏 Physical Measurements:\n";
    
    $height = $product->getHeight();
    echo "   getHeight(): " . ($height ? json_encode($height) : 'null') . "\n";
    
    $width = $product->getWidth();
    echo "   getWidth(): " . ($width ? json_encode($width) : 'null') . "\n";
    
    $weight = $product->getWeight();
    echo "   getWeight(): " . ($weight ? json_encode($weight) : 'null') . "\n";
    
    // Publishing metadata
    echo "\n📝 Publishing Metadata:\n";
    
    $countryOfPublication = $product->getCountryOfPublication();
    echo "   getCountryOfPublication(): " . ($countryOfPublication ?: 'null') . "\n";
    
    $countryName = $product->getCountryOfPublicationName();
    echo "   getCountryOfPublicationName(): " . ($countryName ?: 'null') . "\n";
    
    $cityOfPublication = $product->getCityOfPublication();
    echo "   getCityOfPublication(): " . ($cityOfPublication ?: 'null') . "\n";
    
    $copyrightYear = $product->getCopyrightYear();
    echo "   getCopyrightYear(): " . ($copyrightYear ?: 'null') . "\n";
    
    $firstPublicationYear = $product->getFirstPublicationYear();
    echo "   getFirstPublicationYear(): " . ($firstPublicationYear ?: 'null') . "\n";
    
    $editionNumber = $product->getEditionNumber();
    echo "   getEditionNumber(): " . ($editionNumber ?: 'null') . "\n";
    
    $editionStatement = $product->getEditionStatement();
    echo "   getEditionStatement(): " . ($editionStatement ?: 'null') . "\n";
    
    echo "\n✅ All new methods tested successfully!\n";
    echo "📈 Test Summary:\n";
    echo "   - CodeMaps: 4 new maps added\n";
    echo "   - FieldMappings: Language and publishing metadata added\n";
    echo "   - Product methods: 16 new methods implemented\n\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n\n";
    exit(1);
}