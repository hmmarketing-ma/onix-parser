<?php

namespace ONIXParser\Tests\Support;

/**
 * Writes small synthetic DILICOM-like ONIX files for tests (reference tags, default namespace).
 */
class OnixFile
{
    /**
     * @param int $records number of products
     * @param callable|null $extra fn(int $i): string, extra XML placed inside each product (before </Product>)
     * @return string path (caller deletes it)
     */
    public static function create(int $records, ?callable $extra = null, string $eol = "\n"): string
    {
        $path = tempnam(sys_get_temp_dir(), 'onixtest');
        $h = fopen($path, 'wb');
        fwrite($h, '<?xml version="1.0" encoding="UTF-8"?>' . $eol
            . '<ONIXMessage release="3.0" xmlns="' . ProductFactory::NS . '">' . $eol
            . '<Header><Sender><SenderName>DILICOM</SenderName></Sender><MessageNumber>1</MessageNumber><SentDateTime>20261026T120437Z</SentDateTime></Header>' . $eol);
        for ($i = 1; $i <= $records; $i++) {
            fwrite($h, self::product($i, $extra ? $extra($i) : '', $eol));
        }
        fwrite($h, '</ONIXMessage>' . $eol);
        fclose($h);
        return $path;
    }

    public static function ean(int $i): string
    {
        $base = '978' . str_pad((string)$i, 9, '0', STR_PAD_LEFT);
        $sum = 0;
        for ($k = 0; $k < 12; $k++) {
            $sum += (int)$base[$k] * ($k % 2 ? 3 : 1);
        }
        return $base . ((10 - $sum % 10) % 10);
    }

    public static function product(int $i, string $extra = '', string $eol = "\n"): string
    {
        $ean = self::ean($i);
        return '<Product>' . $eol
            . "  <RecordReference>rec.$i</RecordReference>$eol"
            . "  <NotificationType>03</NotificationType>$eol"
            . "  <ProductIdentifier><ProductIDType>03</ProductIDType><IDValue>$ean</IDValue></ProductIdentifier>$eol"
            . "  <DescriptiveDetail><ProductComposition>00</ProductComposition><ProductForm>BC</ProductForm>"
            . "<TitleDetail><TitleType>01</TitleType><TitleElement><TitleElementLevel>01</TitleElementLevel><TitleText>Titre $i é</TitleText></TitleElement></TitleDetail></DescriptiveDetail>$eol"
            . "  <ProductSupply><SupplyDetail><Supplier><SupplierRole>02</SupplierRole><SupplierIdentifier><SupplierIDType>06</SupplierIDType><IDValue>3012166100000</IDValue></SupplierIdentifier></Supplier>"
            . "<ProductAvailability>20</ProductAvailability></SupplyDetail></ProductSupply>$eol"
            . $extra
            . '</Product>' . $eol;
    }
}
