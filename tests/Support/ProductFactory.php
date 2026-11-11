<?php

namespace ONIXParser\Tests\Support;

use ONIXParser\Model\Product;
use ONIXParser\OnixParser;

/**
 * Builds a Product from a small XML string, the same way the streaming parser does.
 */
class ProductFactory
{
    public const NS = 'http://www.editeur.org/onix/3.0/reference';

    /**
     * @param string $inner XML inside <Product> (no namespace needed)
     */
    public static function fromInner(string $inner, string $ref = 'test.1', string $notification = '03'): Product
    {
        $xml = '<Product xmlns="' . self::NS . '"><RecordReference>' . $ref . '</RecordReference>'
            . '<NotificationType>' . $notification . '</NotificationType>'
            . '<ProductIdentifier><ProductIDType>03</ProductIDType><IDValue>9782091575377</IDValue></ProductIdentifier>'
            . $inner . '</Product>';
        return self::fromXml($xml);
    }

    public static function fromXml(string $xml): Product
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml, LIBXML_NONET);
        return (new OnixParser())->parseProductStreaming($dom->documentElement);
    }

    /** A SupplyDetail snippet */
    public static function supply(string $gln, string $availability, string $prices = '', string $extra = '', string $name = ''): string
    {
        return '<SupplyDetail><Supplier><SupplierRole>02</SupplierRole>'
            . '<SupplierIdentifier><SupplierIDType>06</SupplierIDType><IDValue>' . $gln . '</IDValue></SupplierIdentifier>'
            . ($name !== '' ? '<SupplierName>' . $name . '</SupplierName>' : '')
            . '</Supplier>' . $extra
            . '<ProductAvailability>' . $availability . '</ProductAvailability>' . $prices . '</SupplyDetail>';
    }
}
