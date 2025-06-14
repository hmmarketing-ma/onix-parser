# ONIX Parser API Reference

This document provides comprehensive documentation for all classes, methods, and features available in the ONIX Parser library.

## Table of Contents

- [Product Model](#product-model)
- [New Methods (v1.6.0+)](#new-methods-v160)
- [CodeMaps](#codemaps)
- [FieldMappings](#fieldmappings)
- [Price Model](#price-model)
- [Other Models](#other-models)

## Product Model

The `Product` class represents a single product in an ONIX message and provides access to all product metadata.

### New Methods (v1.6.0+)

#### Physical Product Information

##### `getPageCount(): ?int`
Returns the number of pages in the product.

```php
$pageCount = $product->getPageCount(); // 224
```

**Returns:** `int|null` - Number of pages or null if not available
**ONIX Source:** Extent elements with ExtentType '00' (main content) or '07' (total pages)

##### `getHeight(): ?array`
Returns the height measurement with unit information.

```php
$height = $product->getHeight();
// ['value' => 155, 'unit' => 'mm', 'unit_name' => 'Millimeters']
```

**Returns:** `array|null` with keys: `value` (float), `unit` (string), `unit_name` (string)
**ONIX Source:** Measure elements with MeasureType '01'

##### `getWidth(): ?array`
Returns the width measurement with unit information.

```php
$width = $product->getWidth();
// ['value' => 105, 'unit' => 'mm', 'unit_name' => 'Millimeters']
```

**Returns:** `array|null` with keys: `value` (float), `unit` (string), `unit_name` (string)
**ONIX Source:** Measure elements with MeasureType '02'

##### `getThickness(): ?array`
Returns the thickness measurement with unit information.

```php
$thickness = $product->getThickness();
// ['value' => 12, 'unit' => 'mm', 'unit_name' => 'Millimeters']
```

**Returns:** `array|null` with keys: `value` (float), `unit` (string), `unit_name` (string)
**ONIX Source:** Measure elements with MeasureType '03'

##### `getWeight(): ?array`
Returns the weight measurement with unit information.

```php
$weight = $product->getWeight();
// ['value' => 224, 'unit' => 'gr', 'unit_name' => 'Grams']
```

**Returns:** `array|null` with keys: `value` (float), `unit` (string), `unit_name` (string)
**ONIX Source:** Measure elements with MeasureType '08'

#### Language Information

##### `getLanguageCode(): ?string`
Returns the primary language code (ISO 639).

```php
$languageCode = $product->getLanguageCode(); // 'fra'
```

**Returns:** `string|null` - ISO 639 language code
**ONIX Source:** Language elements with LanguageRole '01' (primary), fallback to any language

##### `getLanguageName(): ?string`
Returns the human-readable language name.

```php
$languageName = $product->getLanguageName(); // 'French'
```

**Returns:** `string|null` - Human-readable language name using CodeMaps
**Dependencies:** Uses `getLanguageCode()` and `CodeMaps::getLanguageCodeMap()`

#### Availability Information

##### `getAvailabilityName(): ?string`
Returns the human-readable availability status.

```php
$availabilityName = $product->getAvailabilityName(); // 'Available'
```

**Returns:** `string|null` - Human-readable availability status
**Dependencies:** Uses `getAvailabilityCode()` and `CodeMaps::getAvailabilityCodeMap()`

#### Product Classification

##### `getProductFormDetail(): ?string`
Returns the product form detail code.

```php
$productFormDetail = $product->getProductFormDetail(); // 'A103'
```

**Returns:** `string|null` - ONIX List 175 product form detail code
**ONIX Source:** ProductFormDetail elements

##### `getProductFormDetailName(): ?string`
Returns the human-readable product form detail name.

```php
$productFormDetailName = $product->getProductFormDetailName();
// 'Trade paperback (UK mass-market paperback)'
```

**Returns:** `string|null` - Human-readable product form detail name
**Dependencies:** Uses `getProductFormDetail()` and `CodeMaps::getProductFormDetailMap()`

#### Publishing Information

##### `getImprintName(): ?string`
Returns the imprint name.

```php
$imprintName = $product->getImprintName(); // 'HACHETTE TOURI'
```

**Returns:** `string|null` - Publisher imprint name
**ONIX Source:** Imprint/ImprintName elements

##### `getCountryOfPublication(): ?string`
Returns the country of publication code.

```php
$countryOfPublication = $product->getCountryOfPublication(); // 'FR'
```

**Returns:** `string|null` - ISO 3166-1 country code
**ONIX Source:** CountryOfPublication elements

##### `getCountryOfPublicationName(): ?string`
Returns the human-readable country of publication name.

```php
$countryName = $product->getCountryOfPublicationName(); // 'France'
```

**Returns:** `string|null` - Human-readable country name
**Dependencies:** Uses `getCountryOfPublication()` and `CodeMaps::getCountryCodeMap()`

##### `getCityOfPublication(): ?string`
Returns the city of publication.

```php
$cityOfPublication = $product->getCityOfPublication(); // 'Paris'
```

**Returns:** `string|null` - City name
**ONIX Source:** CityOfPublication elements

#### Edition and Copyright Information

##### `getCopyrightYear(): ?string`
Returns the copyright year.

```php
$copyrightYear = $product->getCopyrightYear(); // '2023'
```

**Returns:** `string|null` - Copyright year
**ONIX Source:** CopyrightStatement/CopyrightYear elements

##### `getFirstPublicationYear(): ?string`
Returns the first publication year extracted from publication date.

```php
$firstPublicationYear = $product->getFirstPublicationYear(); // '2023'
```

**Returns:** `string|null` - Publication year (first 4 characters of publication date)
**Dependencies:** Uses existing `getPublicationDate()` method

##### `getEditionNumber(): ?string`
Returns the edition number.

```php
$editionNumber = $product->getEditionNumber(); // '2'
```

**Returns:** `string|null` - Edition number
**ONIX Source:** EditionNumber elements

##### `getEditionStatement(): ?string`
Returns the edition statement.

```php
$editionStatement = $product->getEditionStatement(); // 'Second edition'
```

**Returns:** `string|null` - Edition statement
**ONIX Source:** EditionStatement elements

### Existing Methods (Enhanced Documentation)

#### Basic Product Information

##### `getIsbn(): ?string`
Returns the ISBN-13 identifier.

##### `getEan(): ?string`
Returns the EAN/GTIN-13 identifier.

##### `getTitleText(): ?string`
Returns the main title text.

##### `getSubtitle(): ?string`
Returns the subtitle.

##### `getPublisherName(): ?string`
Returns the publisher name.

##### `getPublicationDate(): ?string`
Returns the publication date.

##### `getAnnouncementDate(): ?string`
Returns the announcement date.

##### `getAvailabilityDate(): ?string`
Returns the availability date.

##### `getAvailabilityCode(): ?string`
Returns the availability code.

##### `getProductForm(): ?string`
Returns the product form code.

##### `getProductFormName(): ?string`
Returns the human-readable product form name.

## CodeMaps

The `CodeMaps` class provides mappings between ONIX codes and human-readable descriptions.

### New Methods (v1.6.0+)

#### `getProductFormDetailMap(): array`
Returns mapping of product form detail codes to names.

```php
$map = CodeMaps::getProductFormDetailMap();
// [
//     'A101' => 'Hardcover book with dust jacket',
//     'A102' => 'Hardcover book without dust jacket',
//     'A103' => 'Trade paperback (UK mass-market paperback)',
//     // ... 19 total entries
// ]
```

**Returns:** `array` - ONIX List 175 product form detail mappings

#### `getLanguageCodeMap(): array`
Returns mapping of language codes to names.

```php
$map = CodeMaps::getLanguageCodeMap();
// [
//     'fre' => 'French',
//     'fra' => 'French',
//     'eng' => 'English',
//     'spa' => 'Spanish',
//     // ... 29 total entries
// ]
```

**Returns:** `array` - ISO 639 language code mappings

#### `getCountryCodeMap(): array`
Returns mapping of country codes to names.

```php
$map = CodeMaps::getCountryCodeMap();
// [
//     'FR' => 'France',
//     'US' => 'United States',
//     'GB' => 'United Kingdom',
//     // ... 30 total entries
// ]
```

**Returns:** `array` - ISO 3166-1 country code mappings

#### `getMeasureUnitMap(): array`
Returns mapping of measurement unit codes to names.

```php
$map = CodeMaps::getMeasureUnitMap();
// [
//     'mm' => 'Millimeters',
//     'cm' => 'Centimeters',
//     'gr' => 'Grams',
//     // ... 9 total entries
// ]
```

**Returns:** `array` - ONIX List 50 measurement unit mappings

### Enhanced Methods

#### `getAllMaps(): array`
Now includes all new code mappings.

```php
$allMaps = CodeMaps::getAllMaps();
// Now includes: 'product_form_detail', 'language_code', 'country_code', 'measure_unit'
```

## FieldMappings

The `FieldMappings` class provides XPath mappings for ONIX elements supporting both namespaced and non-namespaced XML.

### New Mappings (v1.6.0+)

#### Language Mappings
```php
$mappings = FieldMappings::getMappings();
$languageMappings = $mappings['language'];
// [
//     'nodes' => ['./onix:DescriptiveDetail/onix:Language', './DescriptiveDetail/Language'],
//     'role' => ['./onix:LanguageRole', './LanguageRole'],
//     'code' => ['./onix:LanguageCode', './LanguageCode'],
//     'primary' => [".//onix:Language[onix:LanguageRole='01']/onix:LanguageCode", ...]
// ]
```

#### Publishing Metadata Mappings
```php
$publishingMappings = $mappings['publishing_metadata'];
// [
//     'country_of_publication' => ['./onix:PublishingDetail/onix:CountryOfPublication', ...],
//     'city_of_publication' => ['./onix:PublishingDetail/onix:CityOfPublication', ...],
//     'copyright_year' => ['.//onix:CopyrightStatement/onix:CopyrightYear', ...],
//     'edition_number' => ['./onix:DescriptiveDetail/onix:EditionNumber', ...],
//     'edition_statement' => ['./onix:DescriptiveDetail/onix:EditionStatement', ...]
// ]
```

## Price Model

### Enhanced Methods (v1.6.0+)

#### `getPriceTypeName(): ?string`
Returns the human-readable price type name.

```php
$price = $product->getDefaultPrice();
$priceTypeName = $price->getPriceTypeName();
// 'Fixed retail price including tax'
```

**Returns:** `string|null` - Human-readable price type name
**Dependencies:** Uses `getType()` and `CodeMaps::getPriceTypeMap()`

## Usage Examples

### Complete Product Information Extraction

```php
use ONIXParser\OnixParser;

$parser = new OnixParser();
$onix = $parser->parseFile('onix_file.xml');

foreach ($onix->getProducts() as $product) {
    // Basic information
    $productInfo = [
        'isbn' => $product->getIsbn(),
        'title' => $product->getTitleText(),
        'subtitle' => $product->getSubtitle(),
        
        // Physical details
        'pages' => $product->getPageCount(),
        'dimensions' => [
            'height' => $product->getHeight(),
            'width' => $product->getWidth(),
            'thickness' => $product->getThickness(),
            'weight' => $product->getWeight()
        ],
        
        // Language
        'language' => [
            'code' => $product->getLanguageCode(),
            'name' => $product->getLanguageName()
        ],
        
        // Publishing
        'publisher' => $product->getPublisherName(),
        'imprint' => $product->getImprintName(),
        'country' => [
            'code' => $product->getCountryOfPublication(),
            'name' => $product->getCountryOfPublicationName()
        ],
        'city' => $product->getCityOfPublication(),
        
        // Edition
        'edition' => [
            'number' => $product->getEditionNumber(),
            'statement' => $product->getEditionStatement(),
            'copyright_year' => $product->getCopyrightYear(),
            'first_publication_year' => $product->getFirstPublicationYear()
        ],
        
        // Availability
        'availability' => [
            'code' => $product->getAvailabilityCode(),
            'name' => $product->getAvailabilityName()
        ],
        
        // Product form
        'product_form' => [
            'code' => $product->getProductForm(),
            'name' => $product->getProductFormName(),
            'detail_code' => $product->getProductFormDetail(),
            'detail_name' => $product->getProductFormDetailName()
        ]
    ];
    
    // Process product information
    processProduct($productInfo);
}
```

### Working with Measurements

```php
$height = $product->getHeight();
if ($height) {
    echo "Height: {$height['value']} {$height['unit']} ({$height['unit_name']})";
    // Output: "Height: 155 mm (Millimeters)"
    
    // Convert to different units if needed
    if ($height['unit'] === 'mm') {
        $heightInCm = $height['value'] / 10;
        echo "Height: {$heightInCm} cm";
    }
}
```

### Price Information with Type Names

```php
foreach ($product->getPrices() as $price) {
    $priceInfo = [
        'amount' => $price->getAmount(),
        'currency' => $price->getCurrency(),
        'type_code' => $price->getType(),
        'type_name' => $price->getPriceTypeName(),
        'formatted' => $price->getFormattedPrice()
    ];
    
    echo "{$priceInfo['type_name']}: {$priceInfo['formatted']}\n";
    // Output: "Fixed retail price including tax: 29.99 EUR"
}
```

## Error Handling

All new methods gracefully handle missing data:

```php
// Safe to call even if data is missing
$pageCount = $product->getPageCount(); // Returns null if not available
$height = $product->getHeight(); // Returns null if not available
$languageName = $product->getLanguageName(); // Returns null or language code if mapping not found
```

## Migration Notes

### From v1.5.x to v1.6.0

**No breaking changes** - All existing code continues to work unchanged.

**New features are additive:**
- All new methods return `null` when data is not available
- Existing method behavior is unchanged
- New CodeMaps are optional and don't affect existing functionality

**Recommended updates:**
- Use `getPriceTypeName()` instead of manual price type mapping
- Use `getAvailabilityName()` for human-readable availability status
- Use new physical measurement methods for complete product details
- Use language methods for internationalization support

---

*This API reference covers all new features added in v1.6.0. For complete documentation of existing features, see the main [README.md](../README.md).*