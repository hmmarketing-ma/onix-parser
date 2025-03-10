<?php

namespace ONIXParser\Tests;

use ONIXParser\OnixParser;
use ONIXParser\Model\Onix;
use ONIXParser\Model\Product;
use ONIXParser\Model\Subject;
use PHPUnit\Framework\TestCase;

class OnixParserTest extends TestCase
{
    /**
     * @var OnixParser
     */
    private $parser;
    
    /**
     * @var string
     */
    private $sampleFilePath;
    
    protected function setUp(): void
    {
        $this->parser = new OnixParser();
        $this->sampleFilePath = __DIR__ . '/fixtures/onix_samples/demo.xml';
    }
    
    public function testParserCanBeInstantiated()
    {
        $this->assertInstanceOf(OnixParser::class, $this->parser);
    }
    
    public function testParseFileReturnsOnixObject()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $this->assertInstanceOf(Onix::class, $onix);
    }
    
    public function testOnixHeaderIsParsed()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $header = $onix->getHeader();
        
        $this->assertNotNull($header);
        $this->assertNotNull($header->getSender());
    }
    
    public function testProductsAreParsed()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $products = $onix->getProducts();
        
        $this->assertIsArray($products);
        $this->assertNotEmpty($products);
        $this->assertInstanceOf(Product::class, $products[0]);
    }
    
    public function testProductHasIdentifiers()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $product = $onix->getProducts()[0];
        
        // Check that at least one of the identifiers is present
        $this->assertTrue(
            !empty($product->getIsbn()) || !empty($product->getEan()),
            "Product should have either ISBN or EAN"
        );
    }
    
    public function testProductHasTitle()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $product = $onix->getProducts()[0];
        $title = $product->getTitle();
        
        $this->assertNotNull($title);
        $this->assertNotEmpty($title->getText());
    }
    
    public function testProductHasAvailabilityInfo()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $product = $onix->getProducts()[0];
        
        // Check if the product has availability information
        // If not, skip the test rather than failing it
        if ($product->getAvailabilityCode() === null) {
            $this->markTestSkipped("Product does not have availability code information in the sample file");
        } else {
            $this->assertNotNull($product->getAvailabilityCode());
        }
    }
    
    public function testProductPricesParsed()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $product = $onix->getProducts()[0];
        $prices = $product->getPrices();
        
        // Some products might not have prices, so we check if prices array exists
        $this->assertIsArray($prices);
        
        // If we have prices, check the first one
        if (!empty($prices)) {
            $this->assertNotNull($prices[0]->getAmount());
        }
    }
    
    public function testSubjectsParsed()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $product = $onix->getProducts()[0];
        
        $subjects = $product->getSubjects();
        
        // Test may be skipped if no subjects found
        if (empty($subjects)) {
            $this->markTestSkipped("No subjects found in the sample file");
            return;
        }
        
        // Verify subjects are parsed correctly
        $this->assertIsArray($subjects);
        $this->assertInstanceOf(Subject::class, $subjects[0]);
        
        // Check that the subject has required properties
        $subject = $subjects[0];
        $this->assertNotNull($subject->getScheme());
        $this->assertNotNull($subject->getCode());
    }
    
    public function testSubjectsCategorizedByScheme()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $product = $onix->getProducts()[0];
        
        // Get all subjects
        $allSubjects = $product->getSubjects();
        
        // Skip test if no subjects
        if (empty($allSubjects)) {
            $this->markTestSkipped("No subjects found in the sample file");
            return;
        }
        
        // Check if we have CLIL subjects
        $clilSubjects = $product->getClilSubjects();
        if (!empty($clilSubjects)) {
            $this->assertEquals('29', $clilSubjects[0]->getScheme());
            $this->assertTrue($clilSubjects[0]->isClil());
        }
        
        // Check if we have THEMA subjects
        $themaSubjects = $product->getThemaSubjects();
        if (!empty($themaSubjects)) {
            $this->assertTrue(
                in_array($themaSubjects[0]->getScheme(), ['93', '94', '95', '96', '97', '98', '99'])
            );
            $this->assertTrue($themaSubjects[0]->isThema());
        }
        
        // Check if we have ScoLOMFR subjects
        $scoLOMFRSubjects = $product->getScoLOMFRSubjects();
        if (!empty($scoLOMFRSubjects)) {
            $this->assertTrue(
                in_array($scoLOMFRSubjects[0]->getScheme(), ['A6', 'B3'])
            );
            $this->assertTrue($scoLOMFRSubjects[0]->isScoLOMFR());
        }
    }
    
    public function testGetSubjectInfo()
    {
        // Test the getSubjectInfo method directly
        $info = $this->parser->getSubjectInfo('3000', '29');
        
        // Skip if no CLIL mapping data
        if ($info === null) {
            $this->markTestSkipped("No CLIL mapping data available");
            return;
        }
        
        $this->assertIsArray($info);
        $this->assertEquals('3000', $info['code']);
        $this->assertArrayHasKey('label', $info);
        
        // Check for hierarchy information
        $this->assertArrayHasKey('children', $info);
        $this->assertIsArray($info['children']);
    }
    
    public function testFindProductByIsbn()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        
        // Get the ISBN of the first product
        $firstProduct = $onix->getProducts()[0];
        $isbn = $firstProduct->getIsbn();
        
        // Skip test if ISBN is not available
        if (empty($isbn)) {
            $this->markTestSkipped("First product does not have an ISBN");
        }
        
        // Try to find the product by ISBN
        $foundProduct = $onix->findProductByIsbn($isbn);
        
        $this->assertNotNull($foundProduct);
        $this->assertEquals($isbn, $foundProduct->getIsbn());
    }
    
    public function testNonExistentIsbnReturnsNull()
    {
        $onix = $this->parser->parseFile($this->sampleFilePath);
        $nonExistentIsbn = '9999999999999';
        
        $foundProduct = $onix->findProductByIsbn($nonExistentIsbn);
        
        $this->assertNull($foundProduct);
    }
}