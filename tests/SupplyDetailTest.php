<?php

namespace ONIXParser\Tests;

use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

class SupplyDetailTest extends TestCase
{
    private function price(string $type, string $amount, string $territory): string
    {
        return "<Price><PriceType>$type</PriceType><PriceAmount>$amount</PriceAmount><CurrencyCode>EUR</CurrencyCode><Territory>$territory</Territory></Price>";
    }

    /** Synthetic record: two distributors, different GLN, availability and price (real files have only one). */
    private function twoSuppliers()
    {
        $ret1 = '<ReturnsConditions><ReturnsCodeType>01</ReturnsCodeType><ReturnsCode>2</ReturnsCode></ReturnsConditions>'
            . '<SupplyDate><SupplyDateRole>18</SupplyDateRole><DateFormat>00</DateFormat><Date>20190510</Date></SupplyDate>'
            . '<OrderTime>3</OrderTime>';
        $ret2 = '<ReturnsConditions><ReturnsCodeType>01</ReturnsCodeType><ReturnsCode>1</ReturnsCode></ReturnsConditions>';
        return F::fromInner('<ProductSupply>'
            . F::supply('3012166100000', '20', $this->price('01', '10.00', '<RegionsIncluded>WORLD</RegionsIncluded><CountriesExcluded>FR</CountriesExcluded>'), $ret1, 'INTERFORUM')
            . F::supply('3010955600100', '31', $this->price('01', '12.50', '<RegionsIncluded>WORLD</RegionsIncluded>'), $ret2, 'HACHETTE')
            . '</ProductSupply>');
    }

    public function testOneObjectPerSupplyDetail(): void
    {
        $p = $this->twoSuppliers();
        $sds = $p->getSupplyDetails();
        $this->assertCount(2, $sds);
        $this->assertSame('3012166100000', $sds[0]->getSupplierGln());
        $this->assertSame('3010955600100', $sds[1]->getSupplierGln());
        $this->assertSame('INTERFORUM', $sds[0]->getSupplierName());
        $this->assertSame('HACHETTE', $sds[1]->getSupplierName());
    }

    public function testLastSupplyDetailDoesNotOverwriteTheFirst(): void
    {
        $sds = $this->twoSuppliers()->getSupplyDetails();
        $this->assertSame('20', $sds[0]->getAvailability());
        $this->assertSame('31', $sds[1]->getAvailability());
    }

    public function testEachSupplyDetailKeepsItsOwnPrices(): void
    {
        $sds = $this->twoSuppliers()->getSupplyDetails();
        $this->assertCount(1, $sds[0]->getPrices());
        $this->assertSame(10.0, $sds[0]->getPrices()[0]->getAmount());
        $this->assertCount(1, $sds[1]->getPrices());
        $this->assertSame(12.5, $sds[1]->getPrices()[0]->getAmount());
    }

    public function testReturnsSupplyDatesAndOrderTime(): void
    {
        $sd = $this->twoSuppliers()->getSupplyDetails()[0];
        $this->assertSame('01', $sd->getReturnsCodeType());
        $this->assertSame('2', $sd->getReturnsCode());
        $this->assertSame([['role' => '18', 'format' => '00', 'date' => '20190510']], $sd->getSupplyDates());
        $this->assertSame('20190510', $sd->getSupplyDate('18'));
        $this->assertNull($sd->getSupplyDate('02'));
        $this->assertSame('3', $sd->getOrderTime());
        $this->assertNull($this->twoSuppliers()->getSupplyDetails()[1]->getOrderTime());
    }

    public function testLookupByGln(): void
    {
        $p = $this->twoSuppliers();
        $this->assertSame('31', $p->getSupplyDetailByGln('3010955600100')->getAvailability());
        $this->assertNull($p->getSupplyDetailByGln('0000000000000'));
    }

    public function testAvailability43And05NotificationAreOnlyExposed(): void
    {
        $p = F::fromInner('<ProductSupply>' . F::supply('3012166100000', '43') . '</ProductSupply>', 'x.1', '05');
        $this->assertSame('05', $p->getNotificationType());
        $this->assertSame('43', $p->getSupplyDetails()[0]->getAvailability());
    }

    public function testSupplyDateWithDateformatAttribute(): void
    {
        $extra = '<SupplyDate><SupplyDateRole>08</SupplyDateRole><Date dateformat="00">20250101</Date></SupplyDate>';
        $p = F::fromInner('<ProductSupply>' . F::supply('3012166100000', '20', '', $extra) . '</ProductSupply>');
        $this->assertSame([['role' => '08', 'format' => '00', 'date' => '20250101']], $p->getSupplyDetails()[0]->getSupplyDates());
    }

    public function testUnpricedItemType(): void
    {
        $p = F::fromInner('<ProductSupply>' . F::supply('3012600500000', '01', '<UnpricedItemType>02</UnpricedItemType>') . '</ProductSupply>');
        $sd = $p->getSupplyDetails()[0];
        $this->assertSame('02', $sd->getUnpricedItemType());
        $this->assertSame([], $sd->getPrices());
    }

    public function testRecordWithoutSupplyHasNoSupplyDetails(): void
    {
        $this->assertSame([], F::fromInner('')->getSupplyDetails());
    }

    public function testLegacyGettersStillWork(): void
    {
        $p = $this->twoSuppliers();
        // old behaviour: last SupplyDetail wins in the single-value getters
        $this->assertSame('31', $p->getAvailabilityCode());
        $this->assertSame('3010955600100', $p->getSupplierGLN());
        $this->assertCount(2, $p->getPrices());
    }
}
