# ONIX Parser

A modern, object-oriented PHP library for parsing ONIX (ONline Information eXchange) XML files. This library supports both ONIX 3.0 namespaced and non-namespaced XML formats.

## Features

- Full support for ONIX 3.0 XML
- Handles both namespaced and non-namespaced XML
- Memory-efficient streaming parser for large XML files
- Consistent behavior between regular and streaming parsing methods
- Comprehensive subject classification support (CLIL, THEMA, ScoLOMFR)
- Rich support for product descriptions with HTML handling
- Collection and series management with hierarchical relationships
- Support for product images and other media resources
- Supplier information extraction including GLN (Global Location Number)
- Detailed price and availability information
- Modular, object-oriented design
- Comprehensive logging
- Configurable and extensible
- Well-documented code with type hints
- Simple API for accessing ONIX data

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
4. **Error Handling**: Can continue processing even if some products fail to parse
5. **Performance**: Better performance for large files, avoiding timeouts
6. **Consistency**: Extracts the same data as the regular parser, including supplier GLN

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

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request
