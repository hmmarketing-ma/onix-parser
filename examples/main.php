<?php

require_once 'vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Set the path to the ONIX file
$onixFilePath =  'tests/fixtures/onix_samples/Sample_ONIX_3.0.xml';

// Create a logger with DEBUG level to see all messages
$logger = new Logger(Logger::DEBUG);

echo "ONIX Parser Example\n";
echo "==================\n\n";

try {
    // Initialize parser with logger
    echo "Initializing parser...\n";
    $parser = new OnixParser($logger);
    
    // Parse the ONIX file
    echo "Parsing ONIX file: $onixFilePath\n";
    $onix = $parser->parseFile($onixFilePath);
    
    // Display header information
    $header = $onix->getHeader();
    echo "\nONIX Header Information:\n";
    echo "------------------------\n";
    echo "ONIX Version: " . $onix->getVersion() . "\n";
    echo "Sender: " . $header->getSender() . "\n";
    if ($header->getContact()) echo "Contact: " . $header->getContact() . "\n";
    if ($header->getEmail()) echo "Email: " . $header->getEmail() . "\n";
    echo "Sent Date: " . $header->getSentDateTime() . "\n";
    echo "Total Products: " . count($onix->getProducts()) . "\n";
    echo "Available Products: " . count($onix->getProductsAvailable()) . "\n";
    
    // Display product information
    echo "\nProduct Information:\n";
    echo "-------------------\n";
    
    foreach ($onix->getProducts() as $index => $product) {
        echo "\nProduct #" . ($index + 1) . "\n";
        echo "Record Reference: " . $product->getRecordReference() . "\n";
        
        // Display notification type
        if ($product->getNotificationType()) {
            echo "Notification: " . $product->getNotificationType();
            if ($product->getNotificationTypeName()) {
                echo " (" . $product->getNotificationTypeName() . ")";
            }
            echo "\n";
        }
        
        // Display identifiers
        if ($product->getIsbn()) echo "ISBN: " . $product->getIsbn() . "\n";
        if ($product->getEan()) echo "EAN: " . $product->getEan() . "\n";
        
        // Display title
        if ($product->getTitle()) {
            echo "Title: " . $product->getTitle()->getText() . "\n";
            if ($product->getTitle()->getSubtitle()) {
                echo "Subtitle: " . $product->getTitle()->getSubtitle() . "\n";
            }
        }
        
        // Display subject information
        echo "\nSubject Information:\n";

        // Display CLIL subjects
        $clilSubjects = $product->getClilSubjects();
        if (!empty($clilSubjects)) {
            echo "CLIL Subjects (" . count($clilSubjects) . "):\n";
            displaySubjectsHierarchically($clilSubjects, $parser);
        } else {
            echo "No CLIL subjects found\n";
        }

        // Display THEMA subjects
        $themaSubjects = $product->getThemaSubjects();
        if (!empty($themaSubjects)) {
            echo "THEMA Subjects (" . count($themaSubjects) . "):\n";
            displaySubjectsHierarchically($themaSubjects, $parser);
        } else {
            echo "No THEMA subjects found\n";
        }

        // Display ScoLOMFR subjects
        $scoLOMFRSubjects = $product->getScoLOMFRSubjects();
        if (!empty($scoLOMFRSubjects)) {
            echo "ScoLOMFR Subjects (" . count($scoLOMFRSubjects) . "):\n";
            foreach ($scoLOMFRSubjects as $subject) {
                echo "  - ";
                if ($subject->isMainSubject()) echo "[MAIN] ";
                echo $subject->getHeadingText() ?? 'Unknown';
                echo " (Code: " . $subject->getCode() . ", Type: " . $subject->getSchemeName() . ")\n";
            }
        } else {
            echo "No ScoLOMFR subjects found\n";
        }
        
        // Test if product has specific subject codes
        echo "\nSubject Code Tests:\n";
        
        // Test for fiction
        if ($product->hasSubjectCode('3031', '29')) {
            echo "This is a novel (CLIL: 3031)\n";
        }
        
        // Test for thriller
        if ($product->hasSubjectCode('3455', '29')) {
            echo "This is a psychological thriller (CLIL: 3455)\n";
        }
        
        // Display availability
        echo "\nAvailability: " . ($product->isAvailable() ? "Available" : "Not available") . "\n";
        if ($product->getAvailabilityCode()) {
            echo "Availability Code: " . $product->getAvailabilityCode() . "\n";
        }
        
        // Display supplier information
        if ($product->getSupplierName()) {
            echo "Supplier: " . $product->getSupplierName() . "\n";
        }
        
        // Display prices
        if (count($product->getPrices()) > 0) {
            echo "\nPrices:\n";
            foreach ($product->getPrices() as $price) {
                echo "  - ";
                if ($price->getType()) echo "Type: " . $price->getType() . ", ";
                echo "Amount: " . $price->getFormattedPrice();
                if ($price->getTaxRate()) echo ", Tax Rate: " . $price->getTaxRate() . "%";
                echo "\n";
            }
        } else {
            echo "\nNo price information available\n";
        }


        // Display images

        // Image information
        $images = $product->getImages();
        echo "Total images: " . count($images) . "\n";
        
        // Cover images
        $coverImages = $product->getCoverImages();
        echo "Cover images: " . count($coverImages) . "\n";
        
        if ($coverImage = $product->getPrimaryCoverImage()) {
            echo "Primary cover URL: " . $coverImage->getUrl() . "\n";
            
            // Generate HTML image tag
            $imgTag = $coverImage->getImageTag([
                'alt' => 'Cover for ' . $product->getTitle()->getText(),
                'class' => 'book-cover',
                'width' => '300'
            ]);
            
            echo "HTML image tag: " . htmlspecialchars($imgTag) . "\n";
            
            // Get file extension
            echo "File extension: " . $coverImage->getFileExtension() . "\n";
        }
        
        // Sample content
        $sampleContent = $product->getSampleContent();
        echo "Sample content resources: " . count($sampleContent) . "\n";
        
        foreach ($sampleContent as $j => $sample) {
            echo "  Sample " . ($j + 1) . ": " . $sample->getUrl() . "\n";
            
            if ($sample->isImage()) {
                echo "  Type: Image\n";
            } elseif ($sample->isVideo()) {
                echo "  Type: Video\n";
            } elseif ($sample->isAudio()) {
                echo "  Type: Audio\n";
            } else {
                echo "  Type: Other (" . $sample->getMode() . ")\n";
            }
        }
        
        // All images with details
        echo "\nAll Resources:\n";
        foreach ($product->getImages() as $k => $image) {
            echo "  Resource " . ($k + 1) . ":\n";
            echo "    Content Type: " . $image->getContentType() . 
                 " (" . $image->getContentTypeName() . ")\n";
            echo "    Mode: " . $image->getMode() . 
                 " (" . $image->getModeName() . ")\n";
            echo "    URL: " . $image->getUrl() . "\n";
        }
        
        echo "-------------------------------\n";
    }
    
    // Demonstrate search functionality
    echo "\nSearch Functionality Example:\n";
    echo "--------------------------\n";
    
    // Get the ISBN of the first product (if available)
    $firstProduct = $onix->getProducts()[0];
    $searchIsbn = $firstProduct->getIsbn();
    
    if ($searchIsbn) {
        echo "Searching for product with ISBN: $searchIsbn\n";
        $foundProduct = $onix->findProductByIsbn($searchIsbn);
        
        if ($foundProduct) {
            echo "Found product: " . $foundProduct->getTitle()->getText() . "\n";
            
            // Display main subject if available
            $mainSubject = $foundProduct->getMainSubject();
            if ($mainSubject) {
                echo "Main subject: " . $mainSubject->getHeadingText() . " (Code: " . $mainSubject->getCode() . ")\n";
            }
        } else {
            echo "No product found with ISBN $searchIsbn\n";
        }
    } else {
        echo "First product doesn't have ISBN. Trying EAN search instead.\n";
        
        $searchEan = $firstProduct->getEan();
        if ($searchEan) {
            echo "Searching for product with EAN: $searchEan\n";
            $foundProduct = $onix->findProductByEan($searchEan);
            
            if ($foundProduct) {
                echo "Found product: " . $foundProduct->getTitle()->getText() . "\n";
            } else {
                echo "No product found with EAN $searchEan\n";
            }
        } else {
            echo "First product doesn't have EAN either. Cannot demonstrate search functionality.\n";
        }
    }
    
    echo "\nONIX Parser Example Completed Successfully\n";
    
} catch (Exception $e) {
    echo "\nError: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}


/**
 * Display subjects hierarchically
 * 
 * @param array $subjects List of Subject objects
 * @param OnixParser $parser Parser instance to access the subject mappings
 * @param string $indent Current indentation string
 * @param array $processedCodes Codes that have already been processed (to avoid cycles)
 */
function displaySubjectsHierarchically(array $subjects, $parser, $indent = "", $processedCodes = [])
{
    // First, find root subjects (those without parents or whose parents aren't in the list)
    $rootSubjects = [];
    $subjectsByCode = [];
    
    // Index subjects by code for easy lookup
    foreach ($subjects as $subject) {
        $code = $subject->getCode();
        $subjectsByCode[$code] = $subject;
        
        // If subject has no parents or all its parents are already processed, it's a root
        if (!$subject->hasParents() || empty(array_diff($subject->getParents(), $processedCodes))) {
            $rootSubjects[] = $subject;
        }
    }
    
    // Display each root subject and its children
    foreach ($rootSubjects as $rootSubject) {
        $code = $rootSubject->getCode();
        
        // Skip if already processed to avoid cycles
        if (in_array($code, $processedCodes)) {
            continue;
        }
        
        // Display the root subject
        echo $indent . "- ";
        if ($rootSubject->isMainSubject()) echo "[MAIN] ";
        echo $rootSubject->getHeadingText() ?? 'Unknown';
        echo " (Code: " . $code . ")";
        
        // Show description (truncated if too long)
        if ($rootSubject->getDescription()) {
            $description = $rootSubject->getDescription();
            if (strlen($description) > 80) {
                $description = substr($description, 0, 77) . '...';
            }
            echo "\n" . $indent . "  Description: " . $description;
        }
        
        echo "\n";
        
        // Mark this code as processed
        $processedCodes[] = $code;
        
        // Find and display child subjects
        if ($rootSubject->hasChildren()) {
            $childCodes = $rootSubject->getChildren();
            $childSubjects = [];
            
            // Find child subjects that are actually in our list
            foreach ($childCodes as $childCode) {
                if (isset($subjectsByCode[$childCode])) {
                    $childSubjects[] = $subjectsByCode[$childCode];
                } else {
                    // If we don't have the child subject in our list, try to get info from parser
                    $childInfo = $parser->getSubjectInfo($childCode, $rootSubject->getScheme());
                    if ($childInfo) {
                        echo $indent . "  └─ " . $childInfo['label'] . " (Code: " . $childCode . ")\n";
                    } else {
                        echo $indent . "  └─ Unknown child (Code: " . $childCode . ")\n";
                    }
                }
            }
            
            // Recursively display children that are in our subject list
            if (!empty($childSubjects)) {
                displaySubjectsHierarchically($childSubjects, $parser, $indent . "  ", $processedCodes);
            }
        }
    }
}