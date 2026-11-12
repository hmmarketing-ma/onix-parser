<?php

namespace ONIXParser\Tests;

use ONIXParser\ChunkOnixParser;
use ONIXParser\Logger;
use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

/**
 * A record can carry several <ProductSupply> (one per market). v2.0.0 returned only the first one from
 * getBlockXml(). Fixture = two REAL records cut from DIF451053808 (#398) and DIF451048537 (#3768):
 * 1st ProductSupply = FR/BE (or FR), 2nd = WORLD minus FR/BE(/CH), the one that covers Morocco.
 */
class MultiProductSupplyTest extends TestCase
{
    private const FIXTURE = __DIR__ . '/fixtures/real/two-product-supply.xml';

    private function records(bool $blocksOnly = false): array
    {
        $p = new ChunkOnixParser(self::FIXTURE, null, new Logger(Logger::ERROR));
        $p->setCheckpointFile(sys_get_temp_dir() . '/mps' . uniqid() . '.cp');
        $p->setBlocksOnly($blocksOnly);
        $out = [];
        $p->parseWithCheckpoints(function ($product) use (&$out) {
            $out[$product->getRecordReference()] = $product;
            return true;
        });
        return $out;
    }

    /** @dataProvider modes */
    public function testBothProductSupplyElementsAreKept(bool $blocksOnly): void
    {
        $recs = $this->records($blocksOnly);
        $this->assertCount(2, $recs);
        foreach ($recs as $ref => $p) {
            $this->assertSame(1, count(array_keys($p->getPresentBlocks(), 'ProductSupply')), "$ref present once");
            $els = $p->getBlockElements('ProductSupply');
            $this->assertCount(2, $els, "$ref: two ProductSupply elements");
            $this->assertStringContainsString('<RegionsIncluded>WORLD</RegionsIncluded>', $els[1]);
            $this->assertStringNotContainsString('<RegionsIncluded>WORLD</RegionsIncluded>', $els[0]);
            // getBlockXml = all of them, in order
            $this->assertSame($els[0] . $els[1], $p->getBlockXml('ProductSupply'));
            $this->assertSame(2, substr_count($p->getBlockXml('ProductSupply'), '<ProductSupply'));
            $this->assertSame(2, substr_count($p->getBlockXml('ProductSupply'), '</ProductSupply>'));
        }
    }

    public static function modes(): array
    {
        return ['full' => [false], 'blocks-only' => [true]];
    }

    public function testMoroccoPriceAndAvailabilityComeFromTheSecondSupply(): void
    {
        $recs = $this->records();
        $a = $recs['mediabase.hmlivre.com/73436'];
        $details = $a->getSupplyDetails();
        $this->assertCount(2, $details);
        $this->assertFalse($details[0]->getMarketTerritories()[0]->coversCountry('MA'));
        $this->assertTrue($details[1]->getMarketTerritories()[0]->coversCountry('MA'));
        $ma = null;
        foreach ($details as $sd) {
            foreach ($sd->getPrices() as $pr) {
                if ($pr->getTerritory() && $pr->getTerritory()->coversCountry('MA')) {
                    $ma = $pr;
                }
            }
        }
        $this->assertSame('01', $ma->getType());
        $this->assertSame(16.11, $ma->getAmount());
        $this->assertSame('40', $details[1]->getAvailability());

        $b = $recs['PF0002'];
        $this->assertSame('51', $b->getSupplyDetails()[1]->getAvailability());
        $this->assertTrue($b->getSupplyDetails()[1]->getMarketTerritories()[0]->coversCountry('MA'));
        $this->assertFalse($b->getSupplyDetails()[1]->getMarketTerritories()[0]->coversCountry('CH'));
    }

    public function testBlocksOnlyGivesTheSameBlockStringsAsFullMode(): void
    {
        $full = $this->records(false);
        $light = $this->records(true);
        foreach ($full as $ref => $p) {
            $this->assertSame($p->getPresentBlocks(), $light[$ref]->getPresentBlocks());
            foreach ($p->getPresentBlocks() as $b) {
                $this->assertSame($p->getBlockElements($b), $light[$ref]->getBlockElements($b), "$ref $b");
            }
            $this->assertSame($p->getEan(), $light[$ref]->getEan());
            $this->assertSame($p->getNotificationType(), $light[$ref]->getNotificationType());
            $this->assertSame(
                array_map(function ($d) {
                    return [$d->getSupplierGln(), $d->getSupplierName(), $d->getAvailability()];
                }, $p->getSupplyDetails()),
                array_map(function ($d) {
                    return [$d->getSupplierGln(), $d->getSupplierName(), $d->getAvailability()];
                }, $light[$ref]->getSupplyDetails())
            );
        }
        $this->assertNull($light['PF0002']->getXml());
        $this->assertSame([], $light['PF0002']->getPrices());
    }

    public function testBlockXmlKeepsUtf8NotEntities(): void
    {
        $p = F::fromInner('<CollateralDetail><TextContent><TextType>03</TextType><Text>Élève é</Text></TextContent></CollateralDetail>');
        $this->assertStringContainsString('Élève é', $p->getBlockXml('CollateralDetail'));
        $this->assertStringNotContainsString('&#x', $p->getBlockXml('CollateralDetail'));
        $this->assertStringContainsString('Élève é', $p->getXml());
        $this->assertStringNotContainsString('&#x', $p->getXml());
    }

    public function testRepeatedBlockOfAnyNameIsKept(): void
    {
        $p = F::fromInner('<CollateralDetail><TextContent><TextType>03</TextType><Text>one</Text></TextContent></CollateralDetail>'
            . '<CollateralDetail><TextContent><TextType>03</TextType><Text>two</Text></TextContent></CollateralDetail>');
        $this->assertCount(2, $p->getBlockElements('CollateralDetail'));
        $this->assertStringContainsString('two', $p->getBlockXml('CollateralDetail'));
        $this->assertSame([], $p->getBlockElements('Nope'));
    }

    public function testRepeatedCompositesInsideBlocksAreAllParsed(): void
    {
        $inner = '<DescriptiveDetail>'
            . '<Contributor><ContributorRole>A01</ContributorRole><PersonName>A One</PersonName></Contributor>'
            . '<Contributor><ContributorRole>A01</ContributorRole><PersonName>B Two</PersonName></Contributor>'
            . '<Collection><CollectionType>10</CollectionType><TitleDetail><TitleType>01</TitleType><TitleElement><TitleElementLevel>02</TitleElementLevel><TitleText>S1</TitleText></TitleElement></TitleDetail></Collection>'
            . '<Collection><CollectionType>11</CollectionType><TitleDetail><TitleType>01</TitleType><TitleElement><TitleElementLevel>02</TitleElementLevel><TitleText>S2</TitleText></TitleElement></TitleDetail></Collection>'
            . '<Subject><SubjectSchemeIdentifier>93</SubjectSchemeIdentifier><SubjectCode>ABC</SubjectCode></Subject>'
            . '<Subject><SubjectSchemeIdentifier>93</SubjectSchemeIdentifier><SubjectCode>DEF</SubjectCode></Subject>'
            . '</DescriptiveDetail><CollateralDetail>'
            . '<TextContent><TextType>03</TextType><Text>t1</Text></TextContent><TextContent><TextType>02</TextType><Text>t2</Text></TextContent>'
            . '</CollateralDetail>'
            . '<ProductSupply><Market><Territory><CountriesIncluded>FR</CountriesIncluded></Territory></Market>' . F::supply('3012166100000', '20') . '</ProductSupply>'
            . '<ProductSupply><Market><Territory><RegionsIncluded>WORLD</RegionsIncluded><CountriesExcluded>FR</CountriesExcluded></Territory></Market>' . F::supply('3012166100000', '31') . '</ProductSupply>';
        $p = F::fromInner($inner);
        $this->assertCount(2, $p->getContributors());
        $this->assertCount(2, $p->getCollections());
        $this->assertCount(2, $p->getDescriptions());
        $this->assertCount(2, $p->getSupplyDetails());
        $this->assertCount(2, $p->getMarketTerritories());
        $this->assertSame('31', $p->getSupplyDetails()[1]->getAvailability());
    }
}
