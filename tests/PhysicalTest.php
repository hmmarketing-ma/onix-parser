<?php

namespace ONIXParser\Tests;

use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

class PhysicalTest extends TestCase
{
    private function measure(string $type, string $value, string $unit): string
    {
        return "<Measure><MeasureType>$type</MeasureType><Measurement>$value</Measurement><MeasureUnitCode>$unit</MeasureUnitCode></Measure>";
    }

    private function extent(string $type, string $value, string $unit): string
    {
        return "<Extent><ExtentType>$type</ExtentType><ExtentValue>$value</ExtentValue><ExtentUnit>$unit</ExtentUnit></Extent>";
    }

    public function testMeasuresAreReadWithUnits(): void
    {
        $p = F::fromInner('<DescriptiveDetail>'
            . $this->measure('01', '215', 'mm') . $this->measure('02', '21.5', 'cm')
            . $this->measure('03', '15', 'mm') . $this->measure('08', '528', 'gr')
            . '</DescriptiveDetail>');
        $this->assertSame(['value' => 215.0, 'unit' => 'mm', 'unit_name' => 'Millimeters', 'type' => '01'], $p->getHeight());
        $this->assertSame('cm', $p->getWidth()['unit']);
        $this->assertSame(21.5, $p->getWidth()['value']);
        $this->assertSame(15.0, $p->getThickness()['value']);
        $this->assertSame('gr', $p->getWeight()['unit']);
        $this->assertSame(528.0, $p->getWeight()['value']);
        // conversions for the shop
        $this->assertSame(215.0, $p->getHeightMm());
        $this->assertSame(215.0, $p->getWidthMm());
        $this->assertSame(528.0, $p->getWeightGrams());
    }

    public function testKilogramsConvertToGrams(): void
    {
        $p = F::fromInner('<DescriptiveDetail>' . $this->measure('08', '1.2', 'kg') . '</DescriptiveDetail>');
        $this->assertSame('kg', $p->getWeight()['unit']);
        $this->assertSame(1200.0, $p->getWeightGrams());
    }

    public function testMissingMeasureIsNull(): void
    {
        $p = F::fromInner('<DescriptiveDetail>' . $this->measure('01', '215', 'mm') . '</DescriptiveDetail>');
        $this->assertNull($p->getWidth());
        $this->assertNull($p->getWeightGrams());
    }

    public function testPageCountExtentType00Unit03(): void
    {
        $p = F::fromInner('<DescriptiveDetail>' . $this->extent('00', '112', '03') . '</DescriptiveDetail>');
        $this->assertSame(112, $p->getPageCount());
    }

    public function testPageCountExtentType03Unit03(): void
    {
        $p = F::fromInner('<DescriptiveDetail>' . $this->extent('03', '96', '03') . '</DescriptiveDetail>');
        $this->assertSame(96, $p->getPageCount());
    }

    public function testPageCountUnit05FromChecklist(): void
    {
        $p = F::fromInner('<DescriptiveDetail>' . $this->extent('00', '200', '05') . '</DescriptiveDetail>');
        $this->assertSame(200, $p->getPageCount());
    }

    public function testPageCountPrefersUnit03OverUnit05(): void
    {
        $p = F::fromInner('<DescriptiveDetail>' . $this->extent('00', '200', '05') . $this->extent('00', '120', '03') . '</DescriptiveDetail>');
        $this->assertSame(120, $p->getPageCount());
    }

    public function testExtentsAreExposedWithUnit(): void
    {
        $p = F::fromInner('<DescriptiveDetail>' . $this->extent('00', '112', '03') . '</DescriptiveDetail>');
        $this->assertSame([['type' => '00', 'value' => '112', 'unit' => '03']], $p->getExtents());
    }

    public function testNoExtentIsNull(): void
    {
        $this->assertNull(F::fromInner('<DescriptiveDetail/>')->getPageCount());
    }
}
