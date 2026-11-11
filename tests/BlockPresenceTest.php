<?php

namespace ONIXParser\Tests;

use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

class BlockPresenceTest extends TestCase
{
    public function testSupplyOnlyRecord(): void
    {
        $p = F::fromInner('<ProductSupply>' . F::supply('3012166100000', '20') . '</ProductSupply>', 'a.1', '04');
        $this->assertSame(['ProductSupply'], $p->getPresentBlocks());
        $this->assertFalse($p->hasBlock('DescriptiveDetail'));
    }

    public function testCollateralPlusSupplyAsInDay1File(): void
    {
        $p = F::fromInner('<CollateralDetail><TextContent><TextType>03</TextType><ContentAudience>00</ContentAudience><Text>Hello</Text></TextContent></CollateralDetail>'
            . '<ProductSupply>' . F::supply('3012166100000', '20') . '</ProductSupply>', 'a.2', '04');
        $this->assertSame(['CollateralDetail', 'ProductSupply'], $p->getPresentBlocks());
    }

    public function testBlocksAreReportedInMessageOrderEvenIfFileOrderDiffers(): void
    {
        $p = F::fromInner('<ProductSupply/><PublishingDetail/><DescriptiveDetail/><RelatedMaterial/><ContentDetail/><CollateralDetail/>');
        $this->assertSame(['DescriptiveDetail', 'CollateralDetail', 'ContentDetail', 'PublishingDetail', 'RelatedMaterial', 'ProductSupply'], $p->getPresentBlocks());
    }

    public function testEmptyBlockElementCountsAsPresent(): void
    {
        // a present empty block means "this block is now empty" for DILICOM, so it must be visible
        $p = F::fromInner('<CollateralDetail/>');
        $this->assertSame(['CollateralDetail'], $p->getPresentBlocks());
    }

    public function testNoBlocks(): void
    {
        $this->assertSame([], F::fromInner('')->getPresentBlocks());
    }

    public function testNestedElementWithSameNameIsNotABlock(): void
    {
        // a ProductSupply nested somewhere else must not count; only direct children of Product do
        $p = F::fromInner('<DescriptiveDetail><Note><ProductSupply/></Note></DescriptiveDetail>');
        $this->assertSame(['DescriptiveDetail'], $p->getPresentBlocks());
    }

    public function testGetBlockXmlReturnsOnlyThatBlock(): void
    {
        $p = F::fromInner('<CollateralDetail><TextContent><TextType>03</TextType><Text>Hello</Text></TextContent></CollateralDetail>'
            . '<ProductSupply>' . F::supply('3012166100000', '20') . '</ProductSupply>');
        $xml = $p->getBlockXml('CollateralDetail');
        $this->assertStringContainsString('<CollateralDetail', $xml);
        $this->assertStringContainsString('Hello', $xml);
        $this->assertStringNotContainsString('ProductSupply', $xml);
        $this->assertStringNotContainsString('ProductAvailability', $xml);
        // the block XML is well-formed on its own
        $doc = new \DOMDocument();
        $this->assertTrue($doc->loadXML($xml));
        $this->assertSame('CollateralDetail', $doc->documentElement->localName);
    }

    public function testGetBlockXmlNullWhenAbsentOrUnknown(): void
    {
        $p = F::fromInner('<ProductSupply/>');
        $this->assertNull($p->getBlockXml('DescriptiveDetail'));
        $this->assertNull($p->getBlockXml('RecordReference'));
        $this->assertNull($p->getBlockXml('block-2'));
    }
}
