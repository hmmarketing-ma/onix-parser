<?php

use PHPUnit\Framework\TestCase;
use ONIXParser\ResumableOnixParser;
use ONIXParser\Resume\CheckpointManager;
use ONIXParser\Resume\ParserState;
use ONIXParser\Resume\ResumePoint;
use ONIXParser\Logger;

class ResumableParserTest extends TestCase
{
    private $testXmlFile;
    private $checkpointDir;
    private $logger;
    private $checkpointManager;
    private $parser;
    
    protected function setUp(): void
    {
        $this->testXmlFile = __DIR__ . '/fixtures/onix_samples/Sample_ONIX_3.0.xml';
        $this->checkpointDir = sys_get_temp_dir() . '/test_checkpoints';
        $this->logger = new Logger(Logger::DEBUG);
        $this->checkpointManager = new CheckpointManager($this->checkpointDir, $this->logger);
        $this->parser = new ResumableOnixParser($this->logger, $this->checkpointManager);
        
        // Create test checkpoint directory
        if (!is_dir($this->checkpointDir)) {
            mkdir($this->checkpointDir, 0755, true);
        }
    }
    
    protected function tearDown(): void
    {
        // Clean up test checkpoint directory
        if (is_dir($this->checkpointDir)) {
            $files = glob($this->checkpointDir . '/*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
            rmdir($this->checkpointDir);
        }
    }
    
    public function testBasicCheckpointCreation()
    {
        $options = [
            'enable_checkpoints' => true,
            'checkpoint_interval' => 5,
            'limit' => 10,
        ];
        
        $onix = $this->parser->parseFileStreaming($this->testXmlFile, $options);
        
        $this->assertInstanceOf('ONIXParser\Model\Onix', $onix);
        
        // Check that checkpoints were created
        $checkpoints = $this->checkpointManager->listCheckpoints();
        $this->assertGreaterThan(0, count($checkpoints));
    }
    
    public function testCheckpointValidation()
    {
        // Create a test parser state
        $parserState = ParserState::fromParser(
            true,
            'http://www.editeur.org/onix/3.0/reference',
            true,
            true,
            '3.0',
            null,
            10,
            10,
            0,
            'processing'
        );
        
        $this->assertTrue($parserState->validate());
        
        // Test invalid state
        $invalidState = ParserState::fromParser(
            true,
            'http://www.editeur.org/onix/3.0/reference',
            true,
            true,
            '3.0',
            null,
            10,
            15, // Processed more than total
            0,
            'processing'
        );
        
        $this->assertFalse($invalidState->validate());
    }
    
    public function testResumePointSerialization()
    {
        $parserState = ParserState::fromParser(
            true,
            'http://www.editeur.org/onix/3.0/reference',
            true,
            true,
            '3.0',
            null,
            5,
            5,
            0,
            'processing'
        );
        
        $resumePoint = new ResumePoint(
            1000,
            $this->testXmlFile,
            md5_file($this->testXmlFile),
            filesize($this->testXmlFile),
            '<Product>',
            'Product',
            $parserState
        );
        
        // Test serialization
        $array = $resumePoint->toArray();
        $this->assertIsArray($array);
        $this->assertEquals(1000, $array['byte_position']);
        $this->assertEquals($this->testXmlFile, $array['file_path']);
        
        // Test deserialization
        $restored = ResumePoint::fromArray($array);
        $this->assertEquals($resumePoint->getBytePosition(), $restored->getBytePosition());
        $this->assertEquals($resumePoint->getFilePath(), $restored->getFilePath());
    }
    
    public function testCheckpointManagerSaveLoad()
    {
        $parserState = ParserState::fromParser(
            true,
            'http://www.editeur.org/onix/3.0/reference',
            true,
            true,
            '3.0',
            null,
            5,
            5,
            0,
            'processing'
        );
        
        $resumePoint = new ResumePoint(
            1000,
            $this->testXmlFile,
            md5_file($this->testXmlFile),
            filesize($this->testXmlFile),
            '<Product>',
            'Product',
            $parserState
        );
        
        // Save checkpoint
        $checkpointFile = $this->checkpointManager->saveCheckpoint($resumePoint);
        $this->assertFileExists($checkpointFile);
        
        // Load checkpoint
        $loaded = $this->checkpointManager->loadCheckpoint($checkpointFile);
        $this->assertEquals($resumePoint->getBytePosition(), $loaded->getBytePosition());
        $this->assertEquals($resumePoint->getFilePath(), $loaded->getFilePath());
    }
    
    public function testParserStats()
    {
        $options = [
            'enable_checkpoints' => true,
            'checkpoint_interval' => 3,
            'limit' => 5,
        ];
        
        $this->parser->parseFileStreaming($this->testXmlFile, $options);
        
        $stats = $this->parser->getStats();
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('session_products', $stats);
        $this->assertArrayHasKey('checkpoints_enabled', $stats);
        $this->assertTrue($stats['checkpoints_enabled']);
    }
    
    public function testCheckpointCleanup()
    {
        // Create multiple checkpoints
        $parserState = ParserState::fromParser(
            true,
            'http://www.editeur.org/onix/3.0/reference',
            true,
            true,
            '3.0',
            null,
            5,
            5,
            0,
            'processing'
        );
        
        // Create several checkpoints
        for ($i = 0; $i < 5; $i++) {
            $resumePoint = new ResumePoint(
                1000 + $i * 100,
                $this->testXmlFile,
                md5_file($this->testXmlFile),
                filesize($this->testXmlFile),
                '<Product>',
                'Product',
                $parserState
            );
            
            $this->checkpointManager->saveCheckpoint($resumePoint, 'test_' . $i);
        }
        
        $checkpoints = $this->checkpointManager->listCheckpoints();
        $this->assertEquals(5, count($checkpoints));
        
        // Set max checkpoints to 3 and cleanup
        $this->checkpointManager->setMaxCheckpoints(3);
        $cleaned = $this->checkpointManager->cleanupOldCheckpoints();
        
        $this->assertEquals(2, $cleaned);
        
        $remaining = $this->checkpointManager->listCheckpoints();
        $this->assertEquals(3, count($remaining));
    }
    
    public function testParserStateRestoration()
    {
        $originalState = ParserState::fromParser(
            true,
            'http://www.editeur.org/onix/3.0/reference',
            true,
            true,
            '3.0',
            null,
            10,
            10,
            0,
            'processing'
        );
        
        $serialized = $originalState->toArray();
        $restored = ParserState::fromArray($serialized);
        
        $this->assertEquals($originalState->hasNamespace(), $restored->hasNamespace());
        $this->assertEquals($originalState->getNamespaceURI(), $restored->getNamespaceURI());
        $this->assertEquals($originalState->getTotalProductCount(), $restored->getTotalProductCount());
        $this->assertEquals($originalState->getProcessedProductCount(), $restored->getProcessedProductCount());
    }
    
    public function testInvalidCheckpointFile()
    {
        $this->expectException('ONIXParser\Exception\CheckpointException');
        
        $invalidFile = $this->checkpointDir . '/invalid.checkpoint';
        file_put_contents($invalidFile, 'invalid json content');
        
        $this->checkpointManager->loadCheckpoint($invalidFile);
    }
    
    public function testMissingCheckpointFile()
    {
        $this->expectException('ONIXParser\Exception\CheckpointException');
        
        $missingFile = $this->checkpointDir . '/missing.checkpoint';
        $this->checkpointManager->loadCheckpoint($missingFile);
    }
}