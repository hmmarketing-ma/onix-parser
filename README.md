# ONIX Parser

A modern, object-oriented PHP library for parsing ONIX (ONline Information eXchange) XML files. This library supports both ONIX 3.0 namespaced and non-namespaced XML formats.

## Features

- **Full ONIX 3.0 XML Support** - Complete parsing of ONIX 3.0 namespaced and non-namespaced XML
- **Memory-Efficient Streaming** - Stream large XML files without memory issues
- **Rich Product Information** - Extract all product metadata including titles, descriptions, subjects
- **Physical Product Details** - Page count, dimensions (height, width, thickness), weight with unit conversion
- **Language Support** - Primary language detection with ISO 639 code mapping
- **Publishing Metadata** - Publisher, imprint, country/city of publication, edition details
- **Product Classification** - Product form, product form details with human-readable names
- **Availability & Pricing** - Detailed availability status and comprehensive price information
- **Subject Classification** - Full support for CLIL, THEMA, and ScoLOMFR classification schemes
- **Images & Media** - Extract cover images and other media resources with URL validation
- **Collections & Series** - Hierarchical collection and series relationships
- **Supplier Information** - GLN (Global Location Number) and supplier details
- **Code Mapping** - Extensive ONIX code lists with human-readable translations
- **Comprehensive Logging** - Detailed logging with configurable levels
- **Type Safety** - Well-documented code with PHP type hints
- **Extensible Architecture** - Modular design for easy customization

## Requirements

- PHP 7.4 or higher
- DOM and SimpleXML extensions (usually included with PHP)
- Composer for dependency management and autoloading

## Installation

```bash
composer require hm-marketing/onix-parser
```

Or clone the repository and install dependencies:

```bash
git clone https://github.com/your-username/onix-parser.git
cd onix-parser
composer install
```

## Project Structure

```
onix-parser/
├── composer.json
├── README.md
├── Example.php
├── src/
│   ├── CodeMaps.php
│   ├── FieldMappings.php
│   ├── Logger.php
│   ├── OnixParser.php
│   └── Model/
│       ├── Collection.php
│       ├── Description.php
│       ├── Header.php
│       ├── Image.php
│       ├── Onix.php
│       ├── Price.php
│       ├── Product.php
│       ├── Subject.php
│       └── Title.php
├── data/
│   ├── clil_codes.json
│   └── thema_codes.json
├── tests/
│   ├── OnixParserTest.php
│   ├── streaming_test.php
│   └── fixtures/
│       └── onix_samples/
│           └── demo.xml
└── vendor/
    └── ...
```

## Basic Usage

```php
<?php

require_once 'vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Create a parser
$parser = new OnixParser();

try {
    // Parse an ONIX file
    $onix = $parser->parseFile('path/to/your/onix_file.xml');
    
    // Access header information
    echo "Sender: " . $onix->getHeader()->getSender() . "\n";
    echo "Date: " . $onix->getHeader()->getSentDateTime() . "\n";
    
    // Access product information
    foreach ($onix->getProducts() as $product) {
        echo "ISBN: " . $product->getIsbn() . "\n";
        echo "Title: " . $product->getTitle()->getText() . "\n";
        
        // Access subject information
        foreach ($product->getClilSubjects() as $subject) {
            echo "CLIL Subject: " . $subject->getHeadingText() . " (" . $subject->getCode() . ")\n";
        }
        
        // Check availability
        if ($product->isAvailable()) {
            echo "Status: Available\n";
            
            // Get price information
            foreach ($product->getPrices() as $price) {
                echo "Price: " . $price->getFormattedPrice() . "\n";
            }
        } else {
            echo "Status: Not available\n";
        }
        
        // Get cover images
        if ($coverImage = $product->getPrimaryCoverImage()) {
            echo "Cover Image: " . $coverImage->getUrl() . "\n";
            echo "Image HTML: " . $coverImage->getImageTag(['alt' => 'Cover image', 'class' => 'book-cover']) . "\n";
        }
        
        echo "\n";
    }
    
    // Find a specific product by ISBN
    $foundProduct = $onix->findProductByIsbn('9780123456789');
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

See the examples directory for more detailed usage examples.

## New Features (v1.6.0+)

### Enhanced Product Information

The ONIX Parser now provides access to comprehensive product metadata with human-readable translations:

```php
$product = $onix->getProducts()[0];

// Physical product details
$pageCount = $product->getPageCount();                    // 224
$height = $product->getHeight();                          // ['value' => 155, 'unit' => 'mm', 'unit_name' => 'Millimeters']
$width = $product->getWidth();                            // ['value' => 105, 'unit' => 'mm', 'unit_name' => 'Millimeters']  
$thickness = $product->getThickness();                    // ['value' => 12, 'unit' => 'mm', 'unit_name' => 'Millimeters']
$weight = $product->getWeight();                          // ['value' => 224, 'unit' => 'gr', 'unit_name' => 'Grams']

// Language information
$languageCode = $product->getLanguageCode();              // 'fra'
$languageName = $product->getLanguageName();              // 'French'

// Enhanced availability with human-readable names
$availabilityCode = $product->getAvailabilityCode();      // '20'
$availabilityName = $product->getAvailabilityName();      // 'Available'

// Product form details
$productForm = $product->getProductForm();                // 'BC'
$productFormName = $product->getProductFormName();        // 'Paperback'
$productFormDetail = $product->getProductFormDetail();    // 'A103'
$productFormDetailName = $product->getProductFormDetailName(); // 'Trade paperback (UK mass-market paperback)'

// Publishing information
$publisher = $product->getPublisherName();                // 'Hachette Livre'
$imprint = $product->getImprintName();                    // 'HACHETTE TOURI'
$countryOfPublication = $product->getCountryOfPublication(); // 'FR'
$countryName = $product->getCountryOfPublicationName();   // 'France'
$cityOfPublication = $product->getCityOfPublication();    // 'Paris'

// Edition and copyright information
$editionNumber = $product->getEditionNumber();            // '2'
$editionStatement = $product->getEditionStatement();      // 'Second edition'
$copyrightYear = $product->getCopyrightYear();            // '2023'
$firstPublicationYear = $product->getFirstPublicationYear(); // '2023'

// Enhanced pricing with type names
foreach ($product->getPrices() as $price) {
    echo $price->getType();           // '04'
    echo $price->getPriceTypeName();  // 'Fixed retail price including tax'
    echo $price->getFormattedPrice(); // '29.99 EUR'
}
```

### New Code Mappings

Four new comprehensive code mapping systems have been added:

```php
use ONIXParser\CodeMaps;

// Product form detail codes (List 175)
$productFormDetailMap = CodeMaps::getProductFormDetailMap();
// Examples: 'A101' => 'Hardcover book with dust jacket', 'A301' => 'Basic ebook'

// Language codes (ISO 639)
$languageCodeMap = CodeMaps::getLanguageCodeMap();
// Examples: 'fra' => 'French', 'eng' => 'English', 'spa' => 'Spanish'

// Country codes (ISO 3166-1)
$countryCodeMap = CodeMaps::getCountryCodeMap();
// Examples: 'FR' => 'France', 'US' => 'United States', 'GB' => 'United Kingdom'

// Measure unit codes (List 50)
$measureUnitMap = CodeMaps::getMeasureUnitMap();
// Examples: 'mm' => 'Millimeters', 'cm' => 'Centimeters', 'gr' => 'Grams'

// Get all code mappings at once
$allMaps = CodeMaps::getAllMaps();
```

### Field Mappings Enhancements

New XPath mappings have been added for language and publishing metadata:

```php
use ONIXParser\FieldMappings;

$mappings = FieldMappings::getMappings();

// Language mappings
$languageMappings = $mappings['language'];
// Includes: 'primary', 'code', 'role', 'nodes'

// Publishing metadata mappings  
$publishingMappings = $mappings['publishing_metadata'];
// Includes: 'country_of_publication', 'city_of_publication', 'copyright_year', 
//          'edition_number', 'edition_statement'
```

## Batch Processing for Large Files

The ONIX Parser supports batch processing of large XML files using a streaming approach, which is more memory-efficient and helps avoid timeout issues when dealing with very large ONIX files.

```php
<?php

require_once 'vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Create a parser with logger
$logger = new Logger(Logger::INFO, 'onix_streaming_parser.log');
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
    
    // Return true to continue processing, or false to stop
    return true; // Returning false would stop processing after this product
};

// Options for batch processing
$options = [
    'limit' => 100,              // Process only 100 products (0 means no limit)
    'offset' => 0,               // Start from the first product
    'callback' => $productCallback,  // Process each product as it's parsed
    'continue_on_error' => true, // Continue processing if an error occurs
];

// Parse the file using streaming approach
$onix = $parser->parseFileStreaming('path/to/large/onix/file.xml', $options);
```

### Benefits of Streaming Parser

The streaming parser offers several advantages for large ONIX files:

1. **Memory Efficiency**: Processes one product at a time, keeping memory usage low
2. **Batch Processing**: Can process a subset of products (using limit and offset)
3. **Progress Tracking**: Provides real-time feedback through the callback function
4. **Conditional Processing**: Can stop processing when callback returns false
5. **Error Handling**: Can continue processing even if some products fail to parse
6. **Performance**: Better performance for large files, avoiding timeouts
7. **Consistency**: Extracts the same data as the regular parser, including supplier GLN

See `examples/streaming.php` for a complete example of using the streaming parser.

## Subject Classification

The ONIX Parser supports the three classification schemes recommended by CLIL:

1. **CLIL Classification** (mandatory) - scheme identifier '29'
2. **THEMA Classification** - scheme identifiers '93' to '99'
3. **ScoLOMFR Classification** - scheme identifiers 'A6' and 'B3'

The parser uses JSON mapping files to convert subject codes to human-readable names:

- `data/clil_codes.json` - Maps CLIL codes to descriptive names
- `data/thema_codes.json` - Maps THEMA codes to descriptive names

Example of using subject information:

```php
// Get all CLIL subjects
$clilSubjects = $product->getClilSubjects();

// Get the main subject
$mainSubject = $product->getMainSubject();

// Check if product has a specific subject code
if ($product->hasSubjectCode('3455', '29')) {
    echo "This is a psychological thriller!";
}
```

## Working with Images

The ONIX Parser supports extracting images and other media resources from ONIX files:

```php
// Get all images for a product
$images = $product->getImages();

// Get just the cover images
$coverImages = $product->getCoverImages();

// Get the primary cover image
$coverImage = $product->getPrimaryCoverImage();

// Get sample content
$sampleContent = $product->getSampleContent();

// Get images of a specific type
$contributorImages = $product->getImagesByType('04'); // Contributor pictures

// Generate an HTML image tag
if ($coverImage) {
    $imgTag = $coverImage->getImageTag([
        'alt' => 'Book cover for ' . $product->getTitle()->getText(),
        'class' => 'book-cover',
        'width' => '300'
    ]);
    echo $imgTag; // Outputs: <img src="http://example.com/cover.jpg" alt="Book cover for Book Title" class="book-cover" width="300">
}

// Check image type
if ($image->isCoverImage()) {
    echo "This is a cover image";
}

if ($image->isImage()) {
    echo "This is an image (not video or audio)";
}

// Get file extension
$extension = $image->getFileExtension(); // Returns 'jpg', 'png', etc.

// Validate URL
if ($image->hasValidUrl()) {
    echo "The image URL is valid";
}
```

## Working with Collections and Series

The ONIX Parser supports extracting collection and series information from ONIX files, including complex collection structures with multiple titles and levels:

```php
// Check if a product is part of a series or collection
if ($product->isPartOfSeries()) {
    echo "This product is part of a series";
}

if ($product->isPartOfCollection()) {
    echo "This product is part of a collection";
}

// Get all collections and series
$collections = $product->getCollections();

// Get only series
$series = $product->getSeries();

// Get only regular collections
$regularCollections = $product->getRegularCollections();

// Get primary series
$primarySeries = $product->getPrimarySeries();
if ($primarySeries) {
    echo "Series: " . $primarySeries->getDisplayName();
    echo "Series Title: " . $primarySeries->getTitleText();
    echo "Part Number: " . $primarySeries->getPartNumber();
}

// Working with a collection object
$collection = $product->getCollections()[0];
if ($collection) {
    // Get collection type
    if ($collection->isSeries()) {
        echo "This is a series";
    } elseif ($collection->isCollection()) {
        echo "This is a regular collection";
    }
    
    // Get collection title
    echo "Title: " . $collection->getTitleText();
    
    // Get part number within the collection
    echo "Part: " . $collection->getPartNumber();
    
    // Get formatted display name (includes part number if available)
    echo "Display Name: " . $collection->getDisplayName();
    
    // Working with additional titles
    $additionalTitles = $collection->getAdditionalTitles();
    foreach ($additionalTitles as $type => $levelTitles) {
        foreach ($levelTitles as $level => $text) {
            echo "Title (Type $type, Level $level): $text";
        }
    }
    
    // Get titles of a specific type (e.g., abbreviated titles - type 05)
    $abbreviatedTitles = $collection->getTitlesByType('05');
    if ($abbreviatedTitles) {
        foreach ($abbreviatedTitles as $level => $text) {
            echo "Abbreviated title (Level $level): $text";
        }
    }
    
    // Convert to string (same as getDisplayName())
    echo $collection; // Implicitly calls __toString()
}
```

## Working with Descriptions

The ONIX Parser provides comprehensive support for product descriptions:

```php
// Get all descriptions for a product
$descriptions = $product->getDescriptions();

// Get specific types of descriptions
$mainDescription = $product->getMainDescription();
$shortDescription = $product->getShortDescription();
$longDescription = $product->getLongDescription();
$tableOfContents = $product->getTableOfContents();
$reviewQuotes = $product->getReviewQuotes();

// Get descriptions by type code
$featuresDescriptions = $product->getDescriptionsByType('11'); // Features

// Check if a product has a specific description type
if ($product->hasDescriptionType('03')) {
    echo "This product has a long description";
}

// Working with a description object
$description = $product->getMainDescription();
if ($description) {
    // Get the raw content (which may include HTML)
    $content = $description->getContent();
    
    // Get plain text (with HTML stripped if necessary)
    $plainText = $description->getPlainText();
    
    // Get a short excerpt (useful for search results or listings)
    $excerpt = $description->getExcerpt(150, '...');
    
    // Check if description is in HTML format
    if ($description->isHtml()) {
        echo "Description contains HTML formatting";
    }
    
    // Convert to string (same as getPlainText())
    echo $description; // Implicitly calls __toString()
}
```

## Supplier Information

The ONIX Parser extracts supplier information, including the Global Location Number (GLN):

```php
// Get supplier information
$supplierName = $product->getSupplierName();
$supplierRole = $product->getSupplierRole();
$supplierGLN = $product->getSupplierGLN();

// Check if the supplier is a publisher
if ($product->getSupplierRole() === '01') {
    echo "Supplier is the publisher";
}

// Get formatted supplier information
echo "Supplied by: " . $product->getSupplierName() . " (GLN: " . $product->getSupplierGLN() . ")";
```

## Configuration

### Logging

You can configure the logging level when creating the parser:

```php
// Create a logger with debug level
$logger = new Logger(Logger::DEBUG, 'path/to/logfile.log');

// Pass the logger to the parser
$parser = new OnixParser($logger);
```

Available log levels:
- `Logger::ERROR` - Only log errors
- `Logger::WARNING` - Log errors and warnings
- `Logger::INFO` - Log errors, warnings, and informational messages
- `Logger::DEBUG` - Log all messages, including debug information

## Advanced Usage

### Working with Product Objects

The `Product` class provides access to common product attributes:

```php
$product = $onix->getProducts()[0];

// Basic identifiers
$isbn = $product->getIsbn();
$ean = $product->getEan();

// Title information
$title = $product->getTitle()->getText();
$subtitle = $product->getTitle()->getSubtitle();
$fullTitle = $product->getTitle()->getFullTitle();

// Subject information
$allSubjects = $product->getSubjects();
$clilSubjects = $product->getClilSubjects();
$themaSubjects = $product->getThemaSubjects();
$scoLOMFRSubjects = $product->getScoLOMFRSubjects();
$mainSubject = $product->getMainSubject();

// Image information
$allImages = $product->getImages();
$coverImages = $product->getCoverImages();
$primaryCover = $product->getPrimaryCoverImage();

// Availability
$isAvailable = $product->isAvailable();
$availabilityCode = $product->getAvailabilityCode();

// Prices
$defaultPrice = $product->getDefaultPrice();
$allPrices = $product->getPrices();
```

### Accessing Original XML

You can access the original XML representation of ONIX objects:

```php
// Get original XML for the header
$headerXml = $onix->getHeader()->getXml();

// Get original XML for a product
$productXml = $product->getXml();
```

## Extending the Parser

The parser is designed to be extensible. To add support for additional ONIX elements:

1. Add the necessary XPath expressions to `FieldMappings.php`
2. Add any new code lists to `CodeMaps.php`
3. Create new model classes as needed
4. Update the `OnixParser` class to parse the new elements

## License

[MIT License](LICENSE)

## Credits

Developed by [Kiran Mohamed/HM Marketing]

## Missing Methods & Future Enhancements

While this version (1.6.0) adds comprehensive support for most commonly needed ONIX data, some methods that could be added in future releases include:

### Potential Future Methods

#### Contributor Details
- `getContributorDetails()` - Enhanced contributor information with roles and biographical notes
- `getMainAuthor()` - Primary author extraction
- `getTranslator()` - Translator information

#### Advanced Pricing
- `getPriceByType($type)` - Get specific price by type code
- `getLowestPrice()` - Get the lowest available price
- `getPriceHistory()` - Price change tracking (if available in ONIX)

#### Sales Information
- `getSalesRestrictions()` - Territory and sales restrictions
- `getSalesRights()` - Sales rights information
- `getAudienceInformation()` - Target audience details

#### Technical Details
- `getFileFormat()` - For digital products (EPUB, PDF, etc.)
- `getFileSize()` - Digital file size information
- `getTechnicalProtection()` - DRM and protection information

#### Extended Classification
- `getAgeRange()` - Target age range information
- `getReadingLevel()` - Reading difficulty level
- `getAudience()` - Audience code (general, academic, professional, etc.)

#### Related Products
- `getRelatedProducts()` - Related product information
- `getAlternativeFormats()` - Other formats of the same work
- `getReplacedBy()` - Replacement product information

### Requesting New Features

If you need any of these methods or have other requirements, please:

1. **Check the current capabilities** - The library already supports extensive ONIX data extraction
2. **Open an issue** on GitHub describing your use case
3. **Contribute** by implementing the feature and submitting a pull request

### Current Method Coverage

**✅ Fully Implemented:**
- Basic product information (ISBN, title, publisher, dates)
- Physical measurements (dimensions, weight, page count)
- Language information with human-readable names
- Availability status with translations
- Product classification (form, form details)
- Publishing metadata (country, city, edition, copyright)
- Pricing with type descriptions
- Subject classification (CLIL, THEMA, ScoLOMFR)
- Images and media resources
- Collections and series
- Descriptions and content
- Supplier information (including GLN)

**🔄 Partially Implemented:**
- Contributor information (basic roles available, could be enhanced)
- Pricing (comprehensive but could add convenience methods)

**⏳ Not Yet Implemented:**
- Sales restrictions and rights
- Advanced audience targeting
- Technical details for digital products
- Related product relationships
- Advanced contributor biographical information

## API Documentation

For complete API documentation including all methods, parameters, and examples, see:
- [API Reference](docs/API_REFERENCE.md) - Comprehensive method documentation
- [Changelog](CHANGELOG.md) - Detailed version history and changes

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

When adding new methods:
1. **Use FieldMappings** for all XPath queries
2. **Use CodeMaps** for human-readable translations
3. **Add comprehensive documentation** with PHPDoc comments
4. **Include test coverage** for new functionality
5. **Follow existing patterns** for consistency
