<?php

namespace ONIXParser\Tests;

use ONIXParser\Model\Territory;
use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

class PriceModelTest extends TestCase
{
    /** The first record of the real initial file (reference 1.4): FR price 04 and the export price 01. */
    private function firstRecord()
    {
        $prices = '<Price><PriceType>04</PriceType>'
            . '<DiscountCoded><DiscountCodeType>02</DiscountCodeType><DiscountCodeTypeName>INTERFORUM</DiscountCodeTypeName><DiscountCode>M </DiscountCode></DiscountCoded>'
            . '<PriceAmount>19.95</PriceAmount>'
            . '<Tax><TaxType>01</TaxType><TaxRatePercent>5.5</TaxRatePercent><TaxableAmount>18.91</TaxableAmount></Tax>'
            . '<CurrencyCode>EUR</CurrencyCode><Territory><CountriesIncluded>FR</CountriesIncluded></Territory></Price>'
            . '<Price><PriceType>01</PriceType>'
            . '<DiscountCoded><DiscountCodeType>02</DiscountCodeType><DiscountCodeTypeName>INTERFORUM</DiscountCodeTypeName><DiscountCode>M </DiscountCode></DiscountCoded>'
            . '<PriceAmount>18.91</PriceAmount><CurrencyCode>EUR</CurrencyCode>'
            . '<Territory><RegionsIncluded>WORLD</RegionsIncluded><CountriesExcluded>FR BE CA CH</CountriesExcluded></Territory></Price>';
        return F::fromInner('<ProductSupply><Market><Territory><RegionsIncluded>WORLD</RegionsIncluded><CountriesExcluded>CA CH</CountriesExcluded></Territory></Market>'
            . F::supply('3012166100000', '51', $prices) . '</ProductSupply>');
    }

    public function testTwoPricesWithTypeAmountCurrency(): void
    {
        $prices = $this->firstRecord()->getSupplyDetails()[0]->getPrices();
        $this->assertCount(2, $prices);
        $this->assertSame('04', $prices[0]->getType());
        $this->assertSame(19.95, $prices[0]->getAmount());
        $this->assertSame('01', $prices[1]->getType());
        $this->assertSame(18.91, $prices[1]->getAmount());
        $this->assertSame('EUR', $prices[1]->getCurrency());
    }

    public function testTerritoryOfEachPrice(): void
    {
        $prices = $this->firstRecord()->getSupplyDetails()[0]->getPrices();
        $fr = $prices[0]->getTerritory();
        $this->assertSame(['FR'], $fr->getCountriesIncluded());
        $this->assertSame([], $fr->getRegionsIncluded());
        $export = $prices[1]->getTerritory();
        $this->assertSame(['WORLD'], $export->getRegionsIncluded());
        $this->assertSame(['FR', 'BE', 'CA', 'CH'], $export->getCountriesExcluded());
        $this->assertSame([], $export->getRegionsExcluded());
    }

    public function testMoroccoIsCoveredOnlyByTheExportPrice(): void
    {
        $prices = $this->firstRecord()->getSupplyDetails()[0]->getPrices();
        $this->assertFalse($prices[0]->getTerritory()->coversCountry('MA'));
        $this->assertTrue($prices[1]->getTerritory()->coversCountry('MA'));
        $this->assertFalse($prices[1]->getTerritory()->coversCountry('FR'));
        $this->assertTrue($prices[0]->getTerritory()->coversCountry('FR'));
    }

    public function testMarketTerritory(): void
    {
        $m = $this->firstRecord()->getMarketTerritories();
        $this->assertCount(1, $m);
        $this->assertTrue($m[0]->coversCountry('MA'));
        $this->assertFalse($m[0]->coversCountry('CH'));
    }

    public function testTerritoryRegionsExcludedWinAndOtherRegionsNeedHelp(): void
    {
        $t = new Territory(null, 'ROW', null, null);
        $this->assertFalse($t->coversCountry('MA'));
        $this->assertTrue($t->coversCountry('MA', ['ROW']));
        $t2 = new Territory('MA', null, null, 'WORLD');
        $this->assertFalse($t2->coversCountry('MA'));
    }

    public function testTaxFirstRecord(): void
    {
        $taxes = $this->firstRecord()->getSupplyDetails()[0]->getPrices()[0]->getTaxes();
        $this->assertCount(1, $taxes);
        $this->assertSame('01', $taxes[0]->getType());
        $this->assertSame(5.5, $taxes[0]->getRatePercent());
        $this->assertSame(18.91, $taxes[0]->getTaxableAmount());
        $this->assertNull($taxes[0]->getTaxAmount());
        $this->assertNull($taxes[0]->getRateCode());
        $this->assertSame([], $this->firstRecord()->getSupplyDetails()[0]->getPrices()[1]->getTaxes());
    }

    public function testTwoTaxCompositesAreAllKept(): void
    {
        $price = '<Price><PriceType>04</PriceType><PriceAmount>22.11</PriceAmount>'
            . '<Tax><TaxType>01</TaxType><TaxRateCode>R</TaxRateCode><TaxRatePercent>5.50</TaxRatePercent><TaxableAmount>20.96</TaxableAmount><TaxAmount>1.15</TaxAmount></Tax>'
            . '<Tax><TaxType>03</TaxType><TaxRateCode>S</TaxRateCode><TaxRatePercent>20.0</TaxRatePercent></Tax>'
            . '<CurrencyCode>EUR</CurrencyCode></Price>';
        $p = F::fromInner('<ProductSupply>' . F::supply('3010955600100', '20', $price) . '</ProductSupply>');
        $taxes = $p->getSupplyDetails()[0]->getPrices()[0]->getTaxes();
        $this->assertCount(2, $taxes);
        $this->assertSame('R', $taxes[0]->getRateCode());
        $this->assertSame(1.15, $taxes[0]->getTaxAmount());
        $this->assertSame('03', $taxes[1]->getType());
        $this->assertSame(20.0, $taxes[1]->getRatePercent());
        $this->assertSame('20.0', $taxes[1]->getRatePercentRaw());
        // legacy getters read the first Tax
        $this->assertSame('01', $p->getSupplyDetails()[0]->getPrices()[0]->getTaxType());
        $this->assertSame(5.5, $p->getSupplyDetails()[0]->getPrices()[0]->getTaxRate());
    }

    public function testPriceDateRole14QualifierAndStatus(): void
    {
        $price = '<Price><PriceType>01</PriceType><PriceQualifier>05</PriceQualifier><PriceStatus>02</PriceStatus>'
            . '<PriceAmount>9.90</PriceAmount><CurrencyCode>EUR</CurrencyCode>'
            . '<PriceDate><PriceDateRole>14</PriceDateRole><DateFormat>00</DateFormat><Date>20120401</Date></PriceDate>'
            . '<PriceDate><PriceDateRole>15</PriceDateRole><Date dateformat="00">20991231</Date></PriceDate></Price>';
        $pr = F::fromInner('<ProductSupply>' . F::supply('3012166100000', '20', $price) . '</ProductSupply>')->getSupplyDetails()[0]->getPrices()[0];
        $this->assertSame('05', $pr->getPriceQualifier());
        $this->assertSame('02', $pr->getPriceStatus());
        $this->assertSame([
            ['role' => '14', 'format' => '00', 'date' => '20120401'],
            ['role' => '15', 'format' => '00', 'date' => '20991231'],
        ], $pr->getDates());
        $this->assertSame('20120401', $pr->getDate('14'));
        $this->assertNull($pr->getDate('13'));
    }

    public function testDiscountCoded(): void
    {
        $d = $this->firstRecord()->getSupplyDetails()[0]->getPrices()[0]->getDiscountCodes();
        $this->assertSame([['type' => '02', 'name' => 'INTERFORUM', 'code' => 'M']], $d);
    }

    public function testUnpricedSupplyDetailHasNoPrice(): void
    {
        $p = F::fromInner('<ProductSupply>' . F::supply('3012600500000', '01', '<UnpricedItemType>02</UnpricedItemType>') . '</ProductSupply>');
        $sd = $p->getSupplyDetails()[0];
        $this->assertSame('02', $sd->getUnpricedItemType());
        $this->assertCount(0, $sd->getPrices());
        $this->assertCount(0, $p->getPrices());
    }

    public function testPriceWithoutAmountIsStillKeptInTheSupplyDetail(): void
    {
        $price = '<Price><PriceType>01</PriceType><UnpricedItemType>03</UnpricedItemType></Price>';
        $p = F::fromInner('<ProductSupply>' . F::supply('3012600500000', '20', $price) . '</ProductSupply>');
        $this->assertCount(1, $p->getSupplyDetails()[0]->getPrices());
        $this->assertNull($p->getSupplyDetails()[0]->getPrices()[0]->getAmount());
        $this->assertSame('03', $p->getSupplyDetails()[0]->getPrices()[0]->getUnpricedItemType());
        $this->assertCount(0, $p->getPrices());
    }
}
