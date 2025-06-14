# Migration Guide

This guide helps you migrate from older versions of the ONIX Parser and take advantage of new features.

## Version 1.6.0 Migration

### Overview

Version 1.6.0 is **fully backward compatible**. All existing code will continue to work without any changes. This release adds new functionality without breaking existing features.

### What's New

- **16 new Product methods** for enhanced metadata extraction
- **4 new CodeMaps** for human-readable translations
- **Enhanced FieldMappings** with language and publishing metadata support
- **Improved Price model** with type name support

### Recommended Updates

While not required, these updates will enhance your application:

#### 1. Enhanced Product Information

**Before (still works):**
```php
$product = $onix->getProducts()[0];
$title = $product->getTitleText();
$isbn = $product->getIsbn();
$publisher = $product->getPublisherName();
```

**After (recommended):**
```php
$product = $onix->getProducts()[0];

// Enhanced product data
$productData = [
    // Basic info (unchanged)
    'title' => $product->getTitleText(),
    'isbn' => $product->getIsbn(),
    'publisher' => $product->getPublisherName(),
    
    // NEW: Physical details
    'physical' => [
        'pages' => $product->getPageCount(),
        'height' => $product->getHeight(),
        'width' => $product->getWidth(),
        'weight' => $product->getWeight()
    ],
    
    // NEW: Language information
    'language' => [
        'code' => $product->getLanguageCode(),
        'name' => $product->getLanguageName()
    ],
    
    // NEW: Enhanced publishing info
    'publishing' => [
        'imprint' => $product->getImprintName(),
        'country_code' => $product->getCountryOfPublication(),
        'country_name' => $product->getCountryOfPublicationName(),
        'city' => $product->getCityOfPublication()
    ],
    
    // NEW: Enhanced availability
    'availability' => [
        'code' => $product->getAvailabilityCode(),
        'name' => $product->getAvailabilityName() // Human-readable!
    ]
];
```

#### 2. Enhanced Price Information

**Before (still works):**
```php
foreach ($product->getPrices() as $price) {
    echo $price->getType() . ': ' . $price->getFormattedPrice();
    // Output: "04: 29.99 EUR"
}
```

**After (recommended):**
```php
foreach ($product->getPrices() as $price) {
    echo $price->getPriceTypeName() . ': ' . $price->getFormattedPrice();
    // Output: "Fixed retail price including tax: 29.99 EUR"
}
```

#### 3. Product Classification Enhancement

**Before (still works):**
```php
$productForm = $product->getProductForm(); // 'BC'
$productFormName = $product->getProductFormName(); // 'Paperback'
```

**After (recommended):**
```php
$classification = [
    'form' => [
        'code' => $product->getProductForm(),
        'name' => $product->getProductFormName()
    ],
    'detail' => [
        'code' => $product->getProductFormDetail(),
        'name' => $product->getProductFormDetailName()
    ]
];
// Now you have: 'BC' => 'Paperback' AND 'A103' => 'Trade paperback'
```

#### 4. Using New CodeMaps

**New feature - Code mapping utilities:**
```php
use ONIXParser\CodeMaps;

// Get all available mappings
$allMaps = CodeMaps::getAllMaps();

// Language conversion utility
function getLanguageName($code) {
    $languageMap = CodeMaps::getLanguageCodeMap();
    return $languageMap[strtolower($code)] ?? $code;
}

// Country conversion utility
function getCountryName($code) {
    $countryMap = CodeMaps::getCountryCodeMap();
    return $countryMap[$code] ?? $code;
}

// Measurement unit conversion utility
function getUnitName($code) {
    $unitMap = CodeMaps::getMeasureUnitMap();
    return $unitMap[$code] ?? $code;
}
```

### Integration Examples

#### For E-commerce Applications

```php
// Complete product information for e-commerce
function extractProductForEcommerce($product) {
    return [
        // Basic product info
        'sku' => $product->getIsbn(),
        'title' => $product->getTitleText(),
        'description' => $product->getMainDescription()?->getPlainText(),
        
        // Physical attributes for shipping
        'physical' => [
            'weight_grams' => $this->convertToGrams($product->getWeight()),
            'dimensions_mm' => [
                'height' => $product->getHeight()['value'] ?? null,
                'width' => $product->getWidth()['value'] ?? null,
                'depth' => $product->getThickness()['value'] ?? null
            ],
            'page_count' => $product->getPageCount()
        ],
        
        // Inventory status
        'availability' => [
            'status' => $product->getAvailabilityName(),
            'in_stock' => $product->isAvailable()
        ],
        
        // Pricing
        'prices' => array_map(function($price) {
            return [
                'amount' => $price->getAmount(),
                'currency' => $price->getCurrency(),
                'type' => $price->getPriceTypeName(),
                'includes_tax' => $this->priceIncludesTax($price->getType())
            ];
        }, $product->getPrices()),
        
        // Classification for search/filtering
        'category' => [
            'product_form' => $product->getProductFormName(),
            'product_detail' => $product->getProductFormDetailName(),
            'subjects' => array_map(function($subject) {
                return $subject->getHeadingText();
            }, $product->getClilSubjects())
        ],
        
        // Publisher info
        'publisher' => [
            'name' => $product->getPublisherName(),
            'imprint' => $product->getImprintName(),
            'country' => $product->getCountryOfPublicationName()
        ]
    ];
}

private function convertToGrams($weight) {
    if (!$weight) return null;
    
    $value = $weight['value'];
    $unit = $weight['unit'];
    
    switch ($unit) {
        case 'gr': return $value;
        case 'kg': return $value * 1000;
        case 'oz': return $value * 28.35;
        case 'lb': return $value * 453.59;
        default: return $value;
    }
}

private function priceIncludesTax($priceType) {
    return in_array($priceType, ['02', '04', '06', '22', '32', '42']);
}
```

#### For Library Systems

```php
// Complete cataloging information for libraries
function extractProductForLibrary($product) {
    return [
        // Cataloging identifiers
        'isbn' => $product->getIsbn(),
        'ean' => $product->getEan(),
        
        // Bibliographic information
        'title' => $product->getTitleText(),
        'subtitle' => $product->getSubtitle(),
        'contributors' => array_map(function($contributor) {
            return [
                'name' => $contributor->getName(),
                'role' => $contributor->getRoleName()
            ];
        }, $product->getContributors()),
        
        // Publication details
        'publication' => [
            'publisher' => $product->getPublisherName(),
            'imprint' => $product->getImprintName(),
            'place' => $product->getCityOfPublication(),
            'country' => $product->getCountryOfPublicationName(),
            'date' => $product->getPublicationDate(),
            'year' => $product->getFirstPublicationYear()
        ],
        
        // Edition information
        'edition' => [
            'number' => $product->getEditionNumber(),
            'statement' => $product->getEditionStatement(),
            'copyright_year' => $product->getCopyrightYear()
        ],
        
        // Physical description
        'physical' => [
            'pages' => $product->getPageCount(),
            'format' => $product->getProductFormName(),
            'format_detail' => $product->getProductFormDetailName(),
            'dimensions' => $this->formatDimensions($product)
        ],
        
        // Language
        'language' => [
            'code' => $product->getLanguageCode(),
            'name' => $product->getLanguageName()
        ],
        
        // Subject classification
        'subjects' => [
            'clil' => array_map(function($subject) {
                return [
                    'code' => $subject->getCode(),
                    'heading' => $subject->getHeadingText()
                ];
            }, $product->getClilSubjects()),
            'thema' => array_map(function($subject) {
                return [
                    'code' => $subject->getCode(),
                    'heading' => $subject->getHeadingText()
                ];
            }, $product->getThemaSubjects())
        ]
    ];
}

private function formatDimensions($product) {
    $height = $product->getHeight();
    $width = $product->getWidth();
    $thickness = $product->getThickness();
    
    if (!$height && !$width && !$thickness) return null;
    
    $dimensions = [];
    if ($height) $dimensions[] = $height['value'] . $height['unit'];
    if ($width) $dimensions[] = $width['value'] . $width['unit'];
    if ($thickness) $dimensions[] = $thickness['value'] . $thickness['unit'];
    
    return implode(' × ', $dimensions);
}
```

### Testing Your Migration

After updating your code, test with this verification script:

```php
// Migration verification script
function verifyMigration($onixFile) {
    $parser = new ONIXParser\OnixParser();
    $onix = $parser->parseFile($onixFile);
    $product = $onix->getProducts()[0];
    
    echo "=== Migration Verification ===\n";
    
    // Test new physical methods
    echo "Page count: " . ($product->getPageCount() ?? 'Not available') . "\n";
    echo "Height: " . json_encode($product->getHeight()) . "\n";
    echo "Weight: " . json_encode($product->getWeight()) . "\n";
    
    // Test new language methods
    echo "Language: " . ($product->getLanguageName() ?? 'Not available') . "\n";
    
    // Test new availability method
    echo "Availability: " . ($product->getAvailabilityName() ?? 'Not available') . "\n";
    
    // Test new publishing methods
    echo "Imprint: " . ($product->getImprintName() ?? 'Not available') . "\n";
    echo "Country: " . ($product->getCountryOfPublicationName() ?? 'Not available') . "\n";
    
    // Test enhanced price methods
    foreach ($product->getPrices() as $price) {
        echo "Price: " . ($price->getPriceTypeName() ?? 'Unknown type') . " - " . $price->getFormattedPrice() . "\n";
    }
    
    echo "\n✅ Migration verification complete!\n";
}
```

### Performance Considerations

The new methods are designed to be efficient:

- **Lazy evaluation** - Data is only extracted when requested
- **Caching** - Results are cached within the Product object
- **Memory efficient** - No increase in base memory usage
- **Streaming compatible** - All new methods work with the streaming parser

### Common Issues and Solutions

#### Issue: Namespace Warnings
```
Warning: SimpleXMLElement::xpath(): Undefined namespace prefix
```

**Solution:** These warnings are expected when processing namespaced XML and don't affect functionality. To suppress them in production:

```php
// Suppress namespace warnings (optional)
error_reporting(E_ALL & ~E_WARNING);
```

#### Issue: Missing Data Returns Null
```php
$pageCount = $product->getPageCount(); // null
```

**Solution:** This is expected behavior. Always check for null values:

```php
$pageCount = $product->getPageCount();
if ($pageCount !== null) {
    echo "Pages: $pageCount";
} else {
    echo "Page count not available";
}
```

#### Issue: Unit Conversion Needed
```php
$weight = $product->getWeight(); // ['value' => 224, 'unit' => 'gr']
```

**Solution:** Implement unit conversion as needed:

```php
function convertWeight($weight, $targetUnit = 'kg') {
    if (!$weight) return null;
    
    $value = $weight['value'];
    $fromUnit = $weight['unit'];
    
    // Convert to grams first
    $grams = match($fromUnit) {
        'gr' => $value,
        'kg' => $value * 1000,
        'oz' => $value * 28.35,
        'lb' => $value * 453.59,
        default => $value
    };
    
    // Convert to target unit
    return match($targetUnit) {
        'gr' => $grams,
        'kg' => $grams / 1000,
        'oz' => $grams / 28.35,
        'lb' => $grams / 453.59,
        default => $grams
    };
}
```

### Next Steps

1. **Update your dependencies:** `composer update hm-marketing/onix-parser`
2. **Review new features:** Read the [API Reference](API_REFERENCE.md)
3. **Update your code:** Use new methods where beneficial
4. **Test thoroughly:** Verify all functionality with your ONIX files
5. **Consider enhancements:** Implement unit conversions and data validation as needed

### Support

If you encounter any issues during migration:

1. Check the [API Reference](API_REFERENCE.md) for method documentation
2. Review the [Changelog](../CHANGELOG.md) for detailed changes
3. Open an issue on GitHub if you need assistance

---

*This migration guide covers upgrading to version 1.6.0. For older version migrations, please refer to previous documentation.*