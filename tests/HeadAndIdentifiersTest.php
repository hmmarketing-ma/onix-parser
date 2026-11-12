<?php

namespace ONIXParser\Tests;

use ONIXParser\ChunkOnixParser;
use ONIXParser\Logger;
use ONIXParser\OnixParser;
use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

class HeadAndIdentifiersTest extends TestCase
{
    private function both(string $xml): array
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        $parser = new OnixParser();
        return [$parser->parseProductStreaming($dom->documentElement), $parser->parseProductBlocks($dom->documentElement)];
    }

    private function record(string $inner): string
    {
        return '<Product xmlns="' . F::NS . '"><RecordReference>r.1</RecordReference><NotificationType>03</NotificationType>' . $inner;
    }

    public function testIdentifierOnlyInsideRelatedMaterialDoesNotGiveTheKey(): void
    {
        $xml = $this->record('<ProductIdentifier><ProductIDType>01</ProductIDType><IDValue>PROP1</IDValue></ProductIdentifier>'
            . '<DescriptiveDetail><ProductForm>BC</ProductForm></DescriptiveDetail>'
            . '<RelatedMaterial><RelatedProduct><ProductRelationCode>06</ProductRelationCode>'
            . '<ProductIdentifier><ProductIDType>03</ProductIDType><IDValue>9782091575377</IDValue></ProductIdentifier>'
            . '<ProductIdentifier><ProductIDType>15</ProductIDType><IDValue>9782091575377</IDValue></ProductIdentifier>'
            . '</RelatedProduct></RelatedMaterial></Product>');
        foreach ($this->both($xml) as $p) {
            $this->assertNull($p->getEan());
            $this->assertNull($p->getIsbn());
            $this->assertSame([['type' => '01', 'value' => 'PROP1']], $p->getProductIdentifiers());
        }
    }

    public function testDirectIdentifiersAllReturnedIncludingASecond03(): void
    {
        $xml = $this->record('<ProductIdentifier><ProductIDType>03</ProductIDType><IDValue>9782091575377</IDValue></ProductIdentifier>'
            . '<ProductIdentifier><ProductIDType>03</ProductIDType><IDValue>9780000000002</IDValue></ProductIdentifier>'
            . '<ProductIdentifier><ProductIDType>15</ProductIDType><IDValue>9782091575377</IDValue></ProductIdentifier>'
            . '<ProductSupply/></Product>');
        foreach ($this->both($xml) as $p) {
            $this->assertSame('9782091575377', $p->getEan());
            $this->assertSame('9782091575377', $p->getIsbn());
            $this->assertSame([
                ['type' => '03', 'value' => '9782091575377'],
                ['type' => '03', 'value' => '9780000000002'],
                ['type' => '15', 'value' => '9782091575377'],
            ], $p->getProductIdentifiers());
        }
    }

    public function testHeadXmlStopsAtTheFirstBlockAndKeepsUtf8(): void
    {
        $xml = $this->record('<ProductIdentifier><ProductIDType>03</ProductIDType><IDValue>9782091575377</IDValue></ProductIdentifier>'
            . '<Barcode><BarcodeType>02</BarcodeType></Barcode><!-- é --><CollateralDetail><TextContent><Text>x</Text></TextContent></CollateralDetail>'
            . '<ProductIdentifier><ProductIDType>01</ProductIDType><IDValue>AFTER</IDValue></ProductIdentifier></Product>');
        [$full, $light] = $this->both($xml);
        $this->assertSame($full->getHeadXml(), $light->getHeadXml());
        $head = $full->getHeadXml();
        $this->assertStringStartsWith('<RecordReference>r.1</RecordReference>', $head);
        $this->assertStringContainsString('<Barcode>', $head);
        $this->assertStringNotContainsString('CollateralDetail', $head);
        $this->assertStringNotContainsString('AFTER', $head);
        $this->assertStringContainsString('é', $head);
        $this->assertStringNotContainsString('&#x', $head);
    }

    public function testRecordWithNoBlockHasTheWholeBodyAsHead(): void
    {
        [$full, $light] = $this->both($this->record('</Product>'));
        $this->assertSame('<RecordReference>r.1</RecordReference><NotificationType>03</NotificationType>', $full->getHeadXml());
        $this->assertSame($full->getHeadXml(), $light->getHeadXml());
    }

    public function testSameOutputInBothModesOnTheRealFixture(): void
    {
        $get = function (bool $light) {
            $p = new ChunkOnixParser(__DIR__ . '/fixtures/real/two-product-supply.xml', null, new Logger(Logger::ERROR));
            $p->setCheckpointFile(sys_get_temp_dir() . '/hid' . uniqid() . '.cp');
            $p->setBlocksOnly($light);
            $out = [];
            $p->parseWithCheckpoints(function ($pr) use (&$out) {
                $out[$pr->getRecordReference()] = [$pr->getHeadXml(), $pr->getProductIdentifiers(), $pr->getEan(), $pr->getIsbn()];
                return true;
            });
            return $out;
        };
        $this->assertSame($get(false), $get(true));
        $first = array_values($get(true))[0];
        $this->assertCount(4, $first[1]); // 01, 03, 31, 35 as in the file
        $this->assertSame('9782490501014', $first[2]);
    }
}
