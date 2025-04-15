<?php

require_once 'vendor/autoload.php';

use ONIXParser\OnixParser;
use ONIXParser\Logger;

// Set the path to the ONIX file
$onixFilePath =  'tests/fixtures/onix_samples/451049077.xml';

// Create a logger with DEBUG level to see all messages
$logger = new Logger(Logger::INFO, 'onix_parser_451049077_2.log');

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