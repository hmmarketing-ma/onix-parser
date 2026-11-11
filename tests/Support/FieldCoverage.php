<?php

namespace ONIXParser\Tests\Support;

use ONIXParser\OnixParser;

/**
 * Real-file field coverage harness (AC-12, P-15).
 *
 * Streams a real ONIX file twice in one pass:
 *   - RAW side: counts straight from the XML with its own XPath (no parser code involved);
 *   - PARSER side: the same counts read back from the Product object.
 * Then it compares them per field. Any difference is a mismatch.
 *
 * Every field is a pair of closures. The parser side calls public getters only, and returns
 * null when a getter does not exist yet (method_exists guard). So the harness runs on the
 * old parser too, and shows exactly which fields are not readable yet.
 */
class FieldCoverage
{
    private const NS = 'http://www.editeur.org/onix/3.0/reference';

    /** @var array<string,array{raw:int,parsed:int,bad_records:int,first_bad:?string}> */
    private $stats = [];

    /** @var array<string,array<string,int>> distribution tables: name => [rawCounts, parsedCounts] */
    private $dist = [];

    /** @var int */
    private $records = 0;

    /** @var array<string,int> */
    private $shapes = [];

    /** @var array<string,int> notification types */
    private $notifications = [];

    /**
     * Run over a file. $limit = 0 means the whole file.
     */
    public function run(string $file, int $limit = 0): void
    {
        $parser = new OnixParser();
        $reader = new \XMLReader();
        if (!$reader->open($file, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw new \RuntimeException("Cannot open $file");
        }
        while ($reader->read()) {
            if ($reader->nodeType !== \XMLReader::ELEMENT || $reader->localName !== 'Product') {
                continue;
            }
            $dom = new \DOMDocument();
            $node = $reader->expand($dom);
            if (!$node) {
                continue;
            }
            $dom->appendChild($node);
            $this->compareOne($parser, $dom);
            $this->records++;
            $reader->next();
            if ($limit > 0 && $this->records >= $limit) {
                break;
            }
        }
        $reader->close();
    }

    private function compareOne(OnixParser $parser, \DOMDocument $dom): void
    {
        $x = new \DOMXPath($dom);
        $x->registerNamespace('o', self::NS);
        $root = $dom->documentElement;
        $product = $parser->parseProductStreaming($root);
        $ref = trim((string)$x->evaluate('string(o:RecordReference)'));

        $raw = $this->rawCounts($x, $root);
        $parsed = $this->parsedCounts($product);

        foreach ($raw['counts'] as $field => $rawValue) {
            $parsedValue = $parsed['counts'][$field] ?? null;
            $this->tally($field, $rawValue, $parsedValue, $ref);
        }
        // block shape, record by record: the exact block names, not only how many
        if (method_exists($product, 'getPresentBlocks')) {
            $shape = $product->getPresentBlocks() ? implode('+', $product->getPresentBlocks()) : '(none)';
            $this->tally('block shape (per record)', 1, $shape === $raw['shape'] ? 1 : 0, $ref . " raw={$raw['shape']} parsed=$shape");
        } else {
            $this->tally('block shape (per record)', 1, null, $ref);
        }
        foreach ($raw['dist'] as $name => $codes) {
            foreach ($codes as $code => $n) {
                $this->dist[$name]['raw'][$code] = ($this->dist[$name]['raw'][$code] ?? 0) + $n;
            }
            foreach (($parsed['dist'][$name] ?? []) as $code => $n) {
                $this->dist[$name]['parsed'][$code] = ($this->dist[$name]['parsed'][$code] ?? 0) + $n;
            }
        }
        $this->shapes[$raw['shape']] = ($this->shapes[$raw['shape']] ?? 0) + 1;
        $nt = $raw['notification'];
        $this->notifications[$nt] = ($this->notifications[$nt] ?? 0) + 1;
    }

    private function tally(string $field, int $raw, ?int $parsed, string $ref): void
    {
        if (!isset($this->stats[$field])) {
            $this->stats[$field] = ['raw' => 0, 'parsed' => 0, 'bad_records' => 0, 'first_bad' => null];
        }
        $this->stats[$field]['raw'] += $raw;
        $this->stats[$field]['parsed'] += (int)$parsed;
        if ($parsed === null || $parsed !== $raw) {
            $this->stats[$field]['bad_records']++;
            if ($this->stats[$field]['first_bad'] === null) {
                $this->stats[$field]['first_bad'] = $ref . ($parsed === null ? ' (no getter)' : " raw=$raw parsed=$parsed");
            }
        }
    }

    /**
     * Raw counts, straight from the XML.
     */
    private function rawCounts(\DOMXPath $x, \DOMElement $root): array
    {
        $n = function (string $q) use ($x, $root): int {
            return (int)$x->evaluate("count($q)", $root);
        };
        $codes = function (string $q) use ($x, $root): array {
            $out = [];
            foreach ($x->query($q, $root) as $node) {
                $v = trim($node->textContent);
                if ($v !== '') {
                    $out[$v] = ($out[$v] ?? 0) + 1;
                }
            }
            return $out;
        };
        $nonEmpty = function (string $q) use ($x, $root): int {
            $c = 0;
            foreach ($x->query($q, $root) as $node) {
                if (trim($node->textContent) !== '') {
                    $c++;
                }
            }
            return $c;
        };

        $blocks = [];
        foreach (['DescriptiveDetail', 'CollateralDetail', 'ContentDetail', 'PublishingDetail', 'RelatedMaterial', 'ProductSupply'] as $b) {
            if ($n("o:$b") > 0) {
                $blocks[] = $b;
            }
        }

        // Page count: Extent type 00 or 03 with unit 03 (pages) and a value
        $pages = 0;
        foreach ($x->query('o:DescriptiveDetail/o:Extent', $root) as $e) {
            $t = trim($x->evaluate('string(o:ExtentType)', $e));
            $u = trim($x->evaluate('string(o:ExtentUnit)', $e));
            $v = trim($x->evaluate('string(o:ExtentValue)', $e));
            if (in_array($t, ['00', '03'], true) && $u === '03' && $v !== '') {
                $pages = 1;
                break;
            }
        }
        $measure = function (string $type) use ($x, $root): int {
            foreach ($x->query("o:DescriptiveDetail/o:Measure[o:MeasureType='$type']", $root) as $m) {
                if (trim($x->evaluate('string(o:Measurement)', $m)) !== '') {
                    return 1;
                }
            }
            return 0;
        };

        // texts with content that carry a format (textformat attribute or TextFormat element)
        $textFormat = 0;
        foreach ($x->query('o:CollateralDetail/o:TextContent', $root) as $t) {
            $hasText = trim($x->evaluate('string(o:Text)', $t)) !== '';
            $fmt = trim($x->evaluate('string(o:Text/@textformat)', $t)) . trim($x->evaluate('string(o:TextFormat)', $t));
            if ($hasText && $fmt !== '') {
                $textFormat++;
            }
        }

        $sd = 'o:ProductSupply/o:SupplyDetail';
        $price = "$sd/o:Price";
        $res = 'o:CollateralDetail/o:SupportingResource/o:ResourceVersion';

        return [
            'shape' => $blocks ? implode('+', $blocks) : '(none)',
            'notification' => trim($x->evaluate('string(o:NotificationType)', $root)) ?: '(none)',
            'counts' => [
                'availability (non-empty)' => $nonEmpty("$sd/o:ProductAvailability"),
                'height' => $measure('01'),
                'width' => $measure('02'),
                'thickness' => $measure('03'),
                'weight' => $measure('08'),
                'page count' => $pages,
                'supply details' => $n($sd),
                'supplier GLN (type 06)' => $nonEmpty("$sd/o:Supplier/o:SupplierIdentifier[o:SupplierIDType='06']/o:IDValue"),
                'supplier name' => $nonEmpty("$sd/o:Supplier/o:SupplierName"),
                'returns code (type 01)' => $nonEmpty("$sd/o:ReturnsConditions[o:ReturnsCodeType='01']/o:ReturnsCode"),
                'supply date role 18' => $n("$sd/o:SupplyDate[o:SupplyDateRole='18']"),
                'supply dates (all)' => $n("$sd/o:SupplyDate"),
                'order time' => $nonEmpty("$sd/o:OrderTime"),
                'prices' => $n($price),
                'price territory' => $n("$price/o:Territory"),
                'price qualifier' => $nonEmpty("$price/o:PriceQualifier"),
                'price status' => $nonEmpty("$price/o:PriceStatus"),
                'price dates (all)' => $n("$price/o:PriceDate"),
                'price date role 14' => $n("$price/o:PriceDate[o:PriceDateRole='14']"),
                'tax composites (all)' => $n("$price/o:Tax"),
                'prices with 2+ taxes' => (int)$x->evaluate('count(' . $price . '[count(o:Tax)>1])', $root),
                'discount coded' => $n("$price/o:DiscountCoded"),
                'unpriced item type' => $nonEmpty("$sd/o:UnpricedItemType") + $nonEmpty("$price/o:UnpricedItemType"),
                'present blocks' => count($blocks),
                'imprint GLN (type 06)' => $nonEmpty("o:PublishingDetail/o:Imprint/o:ImprintIdentifier[o:ImprintIDType='06']/o:IDValue"),
                'resource versions' => $n($res),
                'resource height' => $nonEmpty("$res/o:ResourceVersionFeature[o:ResourceVersionFeatureType='02']/o:FeatureValue"),
                'resource width' => $nonEmpty("$res/o:ResourceVersionFeature[o:ResourceVersionFeatureType='03']/o:FeatureValue"),
                'resource bytes' => $nonEmpty("$res/o:ResourceVersionFeature[o:ResourceVersionFeatureType='07']/o:FeatureValue"),
                'resource date role 17' => $n("$res/o:ContentDate[o:ContentDateRole='17']"),
                'text format' => $textFormat,
                'title textcase' => $nonEmpty("o:DescriptiveDetail/o:TitleDetail[o:TitleType='01']/o:TitleElement[o:TitleElementLevel='01']/o:TitleText/@textcase"),
                'publishing dates (all)' => $n('o:PublishingDetail/o:PublishingDate'),
            ],
            'dist' => [
                'availability code' => $codes("$sd/o:ProductAvailability"),
                'price type' => $codes("$price/o:PriceType"),
            ],
        ];
    }

    /**
     * Same numbers, read from the Product object. null = getter not available yet.
     */
    private function parsedCounts(\ONIXParser\Model\Product $p): array
    {
        $has = function (string $method) use ($p): bool {
            return method_exists($p, $method);
        };
        $m = function ($value) {
            return is_array($value) && isset($value['value']) && $value['value'] !== '' ? 1 : 0;
        };

        $counts = [];
        $dist = ['availability code' => [], 'price type' => []];

        // availability: new model if present, else the old single getter
        if ($has('getSupplyDetails')) {
            $n = 0;
            foreach ($p->getSupplyDetails() as $sd) {
                $a = $sd->getAvailability();
                if ($a !== null && $a !== '') {
                    $n++;
                    $dist['availability code'][$a] = ($dist['availability code'][$a] ?? 0) + 1;
                }
            }
            $counts['availability (non-empty)'] = $n;
        } else {
            $a = $p->getAvailabilityCode();
            $counts['availability (non-empty)'] = ($a !== null && $a !== '') ? 1 : 0;
            if ($a !== null && $a !== '') {
                $dist['availability code'][$a] = 1;
            }
        }

        $counts['height'] = $m($p->getHeight());
        $counts['width'] = $m($p->getWidth());
        $counts['thickness'] = $m($p->getThickness());
        $counts['weight'] = $m($p->getWeight());
        $pc = $p->getPageCount();
        $counts['page count'] = ($pc !== null && $pc > 0) ? 1 : 0;

        if ($has('getSupplyDetails')) {
            $sds = $p->getSupplyDetails();
            $c = array_fill_keys([
                'supply details', 'supplier GLN (type 06)', 'supplier name', 'returns code (type 01)',
                'supply date role 18', 'supply dates (all)', 'order time', 'prices', 'price territory',
                'price qualifier', 'price status', 'price dates (all)', 'price date role 14',
                'tax composites (all)', 'prices with 2+ taxes', 'discount coded', 'unpriced item type',
            ], 0);
            foreach ($sds as $sd) {
                $c['supply details']++;
                $c['supplier GLN (type 06)'] += $sd->getSupplierGln() ? 1 : 0;
                $c['supplier name'] += $sd->getSupplierName() ? 1 : 0;
                $c['returns code (type 01)'] += ($sd->getReturnsCodeType() === '01' && (string)$sd->getReturnsCode() !== '') ? 1 : 0;
                foreach ($sd->getSupplyDates() as $d) {
                    $c['supply dates (all)']++;
                    $c['supply date role 18'] += ($d['role'] === '18') ? 1 : 0;
                }
                $c['order time'] += ((string)$sd->getOrderTime() !== '') ? 1 : 0;
                $c['unpriced item type'] += ((string)$sd->getUnpricedItemType() !== '') ? 1 : 0;
                foreach ($sd->getPrices() as $pr) {
                    $c['prices']++;
                    if ($pr->getType() !== null) {
                        $dist['price type'][$pr->getType()] = ($dist['price type'][$pr->getType()] ?? 0) + 1;
                    }
                    if (method_exists($pr, 'getTerritory')) {
                        $c['price territory'] += $pr->getTerritory() ? 1 : 0;
                        $c['price qualifier'] += ((string)$pr->getPriceQualifier() !== '') ? 1 : 0;
                        $c['price status'] += ((string)$pr->getPriceStatus() !== '') ? 1 : 0;
                        foreach ($pr->getDates() as $d) {
                            $c['price dates (all)']++;
                            $c['price date role 14'] += ($d['role'] === '14') ? 1 : 0;
                        }
                        $c['tax composites (all)'] += count($pr->getTaxes());
                        $c['prices with 2+ taxes'] += count($pr->getTaxes()) > 1 ? 1 : 0;
                        $c['discount coded'] += count($pr->getDiscountCodes());
                        $c['unpriced item type'] += ((string)$pr->getUnpricedItemType() !== '') ? 1 : 0;
                    } else {
                        // old model cannot read these: leave them out (null) so they show as a failure
                        foreach (['price territory', 'price qualifier', 'price status', 'price dates (all)',
                                  'price date role 14', 'tax composites (all)', 'prices with 2+ taxes',
                                  'discount coded'] as $k) {
                            unset($c[$k]);
                        }
                    }
                }
            }
            $counts += $c;
        }

        if ($has('getPresentBlocks')) {
            $counts['present blocks'] = count($p->getPresentBlocks());
        }
        if ($has('getImprintGln')) {
            $counts['imprint GLN (type 06)'] = ((string)$p->getImprintGln() !== '') ? 1 : 0;
        }
        if ($has('getResourceVersions')) {
            $rv = $p->getResourceVersions();
            $counts['resource versions'] = count($rv);
            $counts['resource height'] = 0;
            $counts['resource width'] = 0;
            $counts['resource bytes'] = 0;
            $counts['resource date role 17'] = 0;
            foreach ($rv as $v) {
                $counts['resource height'] += ((string)$v->getHeight() !== '') ? 1 : 0;
                $counts['resource width'] += ((string)$v->getWidth() !== '') ? 1 : 0;
                $counts['resource bytes'] += ((string)$v->getBytes() !== '') ? 1 : 0;
                $counts['resource date role 17'] += ($v->getContentDate('17') !== null) ? 1 : 0;
            }
        }

        $d = 0;
        foreach ($p->getDescriptions() as $desc) {
            $d += ((string)$desc->getFormat() !== '') ? 1 : 0;
        }
        $counts['text format'] = $d;
        $counts['title textcase'] = ($p->getTitle() && (string)$p->getTitle()->getTextCase() !== '') ? 1 : 0;
        $counts['publishing dates (all)'] = count($p->getPublishingDates());

        return ['counts' => $counts, 'dist' => $dist];
    }

    public function getRecordCount(): int
    {
        return $this->records;
    }

    /** @return array<string,array> */
    public function getStats(): array
    {
        return $this->stats;
    }

    /** @return array<string,array> */
    public function getDistributions(): array
    {
        return $this->dist;
    }

    /** @return array<string,int> */
    public function getShapes(): array
    {
        arsort($this->shapes);
        return $this->shapes;
    }

    /** @return array<string,int> */
    public function getNotifications(): array
    {
        return $this->notifications;
    }

    /**
     * Fields where parser total != raw total, or any record differs.
     *
     * @return string[]
     */
    public function failures(): array
    {
        $bad = [];
        foreach ($this->stats as $field => $s) {
            if ($s['bad_records'] > 0 || $s['raw'] !== $s['parsed']) {
                $bad[] = $field;
            }
        }
        foreach ($this->dist as $name => $d) {
            $r = $d['raw'] ?? [];
            $p = $d['parsed'] ?? [];
            ksort($r);
            ksort($p);
            if ($r !== $p) {
                $bad[] = "distribution: $name";
            }
        }
        return $bad;
    }

    public function report(): string
    {
        $out = sprintf("Records: %d\n\n", $this->records);
        $out .= sprintf("%-28s %10s %10s %10s  %s\n", 'FIELD', 'RAW', 'PARSER', 'BAD REC', 'RESULT');
        foreach ($this->stats as $field => $s) {
            $ok = $s['bad_records'] === 0 && $s['raw'] === $s['parsed'];
            $out .= sprintf(
                "%-28s %10d %10d %10d  %s%s\n",
                $field,
                $s['raw'],
                $s['parsed'],
                $s['bad_records'],
                $ok ? 'OK' : 'MISMATCH',
                $ok ? '' : '  first: ' . $s['first_bad']
            );
        }
        foreach ($this->dist as $name => $d) {
            $r = $d['raw'] ?? [];
            $p = $d['parsed'] ?? [];
            ksort($r);
            ksort($p);
            $out .= "\nDistribution: $name   " . ($r === $p ? 'OK' : 'MISMATCH') . "\n";
            foreach (array_unique(array_merge(array_keys($r), array_keys($p))) as $code) {
                $out .= sprintf("  %-6s raw %7d  parser %7d\n", $code, $r[$code] ?? 0, $p[$code] ?? 0);
            }
        }
        $out .= "\nNotification types: " . json_encode($this->notifications) . "\n";
        $out .= "Block shapes (top 8):\n";
        foreach (array_slice($this->getShapes(), 0, 8, true) as $shape => $n) {
            $out .= sprintf("  %7d  %s\n", $n, $shape);
        }
        $fails = $this->failures();
        $out .= "\n" . ($fails ? 'FAIL: ' . implode(', ', $fails) : 'PASS: every field matches the raw XML') . "\n";
        return $out;
    }
}
