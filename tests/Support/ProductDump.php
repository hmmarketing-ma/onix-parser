<?php

namespace ONIXParser\Tests\Support;

use ONIXParser\Model\Product;

/**
 * Stable one-line JSON of everything the 2.0 parser reads from a record.
 * Two runs are "the same" when the lines are byte-identical.
 */
class ProductDump
{
    public static function line(Product $p): string
    {
        $supply = [];
        foreach ($p->getSupplyDetails() as $sd) {
            $prices = [];
            foreach ($sd->getPrices() as $pr) {
                $t = $pr->getTerritory();
                $prices[] = [
                    $pr->getType(), $pr->getAmount(), $pr->getCurrency(), $pr->getPriceQualifier(), $pr->getPriceStatus(),
                    $t ? $t->toArray() : null, $pr->getDates(), $pr->getDiscountCodes(), $pr->getUnpricedItemType(),
                    array_map(function ($x) {
                        return [$x->getType(), $x->getRateCode(), $x->getRatePercentRaw(), $x->getTaxableAmount(), $x->getTaxAmount()];
                    }, $pr->getTaxes()),
                ];
            }
            $supply[] = [
                $sd->getSupplierGln(), $sd->getSupplierName(), $sd->getSupplierRole(), $sd->getAvailability(),
                $sd->getReturnsConditions(), $sd->getSupplyDates(), $sd->getOrderTime(), $sd->getUnpricedItemType(), $prices,
            ];
        }
        $resources = array_map(function ($v) {
            return [$v->getContentType(), $v->getUrl(), $v->getHeight(), $v->getWidth(), $v->getBytes(), $v->getContentDate('17')];
        }, $p->getResourceVersions());
        $descriptions = array_map(function ($d) {
            return [$d->getType(), $d->getFormat(), $d->getLanguage(), md5((string)$d->getContent())];
        }, $p->getDescriptions());

        return json_encode([
            'ref' => $p->getRecordReference(),
            'nt' => $p->getNotificationType(),
            'ean' => $p->getEan(),
            'title' => $p->getTitle() ? [$p->getTitle()->getText(), $p->getTitle()->getTextCase()] : null,
            'blocks' => $p->getPresentBlocks(),
            'supply' => $supply,
            'market' => array_map(function ($t) {
                return $t->toArray();
            }, $p->getMarketTerritories()),
            'h' => $p->getHeight(), 'w' => $p->getWidth(), 't' => $p->getThickness(), 'g' => $p->getWeight(),
            'pages' => $p->getPageCount(),
            'imprint' => [$p->getImprintName(), $p->getImprintGln()],
            'pub' => $p->getPublishingDates(),
            'res' => $resources,
            'desc' => $descriptions,
            'contrib' => count($p->getContributors()),
            'avail' => $p->getAvailabilityCode(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}
