<?php

namespace ONIXParser\Tests;

use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

class AvailabilityTest extends TestCase
{
    public function testAvailabilityIsReadFromSupplyDetail(): void
    {
        $p = F::fromInner('<ProductSupply>' . F::supply('3012166100000', '51') . '</ProductSupply>');
        $this->assertSame('51', $p->getAvailabilityCode());
    }

    /**
     * @dataProvider codes
     */
    public function testEveryRealCodeIsExposedUnchanged(string $code): void
    {
        $p = F::fromInner('<ProductSupply>' . F::supply('3012166100000', $code) . '</ProductSupply>');
        $this->assertSame($code, $p->getAvailabilityCode());
    }

    public static function codes(): array
    {
        // codes seen in the real initial file, plus 43 (never seen, parser must only expose it)
        return array_map(fn($c) => [$c], ['20', '51', '31', '41', '52', '10', '30', '32', '23', '01', '46', '45', '43']);
    }

    public function testNoSupplyMeansNoAvailability(): void
    {
        $p = F::fromInner('');
        $this->assertNull($p->getAvailabilityCode());
    }
}
