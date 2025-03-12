# ONIX Parser

A modern, object-oriented PHP library for parsing ONIX (ONline Information eXchange) XML files. This library supports both ONIX 3.0 namespaced and non-namespaced XML formats.

## Features

- Full support for ONIX 3.0 XML
- Handles both namespaced and non-namespaced XML
- Comprehensive subject classification support (CLIL, THEMA, ScoLOMFR)
- Support for product images and other media resources
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
composer require your-vendor/onix-parser
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

See `Example.php` for a more detailed usage example.

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