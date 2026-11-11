<?php

namespace ONIXParser\Tests;

use ONIXParser\ChunkOnixParser;
use ONIXParser\Logger;
use ONIXParser\OnixParser;
use ONIXParser\Tests\Support\OnixFile;
use ONIXParser\Tests\Support\ProductFactory as F;
use PHPUnit\Framework\TestCase;

class AttributesAndResourcesTest extends TestCase
{
    // ---- textformat / textcase / language: attribute and element

    public function testTextFormatAsAttribute(): void
    {
        $p = F::fromInner('<CollateralDetail><TextContent><TextType>03</TextType><ContentAudience>00</ContentAudience><Text textformat="05" language="fre">&lt;p&gt;Bonjour&lt;/p&gt;</Text></TextContent></CollateralDetail>');
        $d = $p->getDescriptions()[0];
        $this->assertSame('05', $d->getFormat());
        $this->assertSame('fre', $d->getLanguage());
        $this->assertSame('<p>Bonjour</p>', $d->getContent());
    }

    public function testTextFormatAsElement(): void
    {
        $p = F::fromInner('<CollateralDetail><TextContent><TextType>03</TextType><TextFormat>02</TextFormat><TextLanguage>eng</TextLanguage><Text>Hello</Text></TextContent></CollateralDetail>');
        $d = $p->getDescriptions()[0];
        $this->assertSame('02', $d->getFormat());
        $this->assertSame('eng', $d->getLanguage());
    }

    public function testTitleTextCaseAndLanguageAsAttribute(): void
    {
        $p = F::fromInner('<DescriptiveDetail><TitleDetail><TitleType>01</TitleType><TitleElement><TitleElementLevel>01</TitleElementLevel>'
            . '<TitleText textcase="01" language="fre">Quelle émotion ?!</TitleText></TitleElement></TitleDetail></DescriptiveDetail>');
        $this->assertSame('Quelle émotion ?!', $p->getTitle()->getText());
        $this->assertSame('01', $p->getTitle()->getTextCase());
        $this->assertSame('fre', $p->getTitle()->getLanguage());
    }

    public function testTitleTextCaseAsElement(): void
    {
        $p = F::fromInner('<DescriptiveDetail><TitleDetail><TitleType>01</TitleType><TitleElement><TitleElementLevel>01</TitleElementLevel>'
            . '<TitleText>Titre</TitleText><TextCase>02</TextCase><TitleLanguage>fre</TitleLanguage></TitleElement></TitleDetail></DescriptiveDetail>');
        $this->assertSame('02', $p->getTitle()->getTextCase());
        $this->assertSame('fre', $p->getTitle()->getLanguage());
    }

    public function testDateFormatAsElementAndAsAttribute(): void
    {
        $p = F::fromInner('<PublishingDetail>'
            . '<PublishingDate><PublishingDateRole>01</PublishingDateRole><DateFormat>00</DateFormat><Date>20080320</Date></PublishingDate>'
            . '<PublishingDate><PublishingDateRole>13</PublishingDateRole><Date dateformat="00">20190509</Date></PublishingDate>'
            . '<PublishingDate><PublishingDateRole>09</PublishingDateRole><Date>20991231</Date></PublishingDate>'
            . '</PublishingDetail>');
        $this->assertSame([
            ['role' => '01', 'format' => '00', 'date' => '20080320'],
            ['role' => '13', 'format' => '00', 'date' => '20190509'],
            ['role' => '09', 'format' => null, 'date' => '20991231'],
        ], $p->getPublishingDates());
    }

    // ---- imprint

    public function testImprintNameAndGln(): void
    {
        $p = F::fromInner('<PublishingDetail><Imprint><ImprintIdentifier><ImprintIDType>06</ImprintIDType><IDValue>3052090010015</IDValue></ImprintIdentifier>'
            . '<ImprintName sourcename="DILICOM" sourcetype="04">NATHAN</ImprintName></Imprint><Publisher><PublishingRole>01</PublishingRole><PublisherName>Nathan</PublisherName></Publisher></PublishingDetail>');
        $this->assertSame('3052090010015', $p->getImprintGln());
        $this->assertSame('NATHAN', $p->getImprintName());
        $this->assertSame('Nathan', $p->getPublisherName());
    }

    public function testImprintIdentifierOfAnotherTypeIsNotTheGln(): void
    {
        $p = F::fromInner('<PublishingDetail><Imprint><ImprintIdentifier><ImprintIDType>01</ImprintIDType><IDValue>XYZ</IDValue></ImprintIdentifier><ImprintName>X</ImprintName></Imprint></PublishingDetail>');
        $this->assertNull($p->getImprintGln());
    }

    // ---- resource versions

    private function resource(string $type, string $size, int $bytes, string $name, string $dateXml = '<ContentDate><ContentDateRole>17</ContentDateRole><Date dateformat="00">20250404</Date></ContentDate>'): string
    {
        return '<SupportingResource><ResourceContentType>' . $type . '</ResourceContentType><ContentAudience>00</ContentAudience><ResourceMode>03</ResourceMode>'
            . '<ResourceVersion><ResourceForm>01</ResourceForm>'
            . '<ResourceVersionFeature><ResourceVersionFeatureType>04</ResourceVersionFeatureType><FeatureValue>' . $name . '.jpg</FeatureValue></ResourceVersionFeature>'
            . '<ResourceVersionFeature><ResourceVersionFeatureType>01</ResourceVersionFeatureType><FeatureValue>D502</FeatureValue></ResourceVersionFeature>'
            . '<ResourceVersionFeature><ResourceVersionFeatureType>02</ResourceVersionFeatureType><FeatureValue>' . $size . '</FeatureValue></ResourceVersionFeature>'
            . '<ResourceVersionFeature><ResourceVersionFeatureType>03</ResourceVersionFeatureType><FeatureValue>' . $size . '</FeatureValue></ResourceVersionFeature>'
            . '<ResourceVersionFeature><ResourceVersionFeatureType>07</ResourceVersionFeatureType><FeatureValue>' . $bytes . '</FeatureValue></ResourceVersionFeature>'
            . '<ResourceLink>https://images.example/' . $name . '.jpg</ResourceLink>' . $dateXml . '</ResourceVersion></SupportingResource>';
    }

    private function covers()
    {
        return F::fromInner('<CollateralDetail>'
            . $this->resource('01', '800', 145380, 'full')
            . $this->resource('01', '400', 40000, 'large')
            . $this->resource('01', '200', 12000, 'medium')
            . $this->resource('01', '80', 2000, 'thumb')
            . $this->resource('15', '300', 9000, 'back')
            . '</CollateralDetail>');
    }

    public function testEveryResourceVersionExposesTypeUrlSizeBytesAndDate(): void
    {
        $versions = $this->covers()->getResourceVersions();
        $this->assertCount(5, $versions);
        $large = $versions[1];
        $this->assertSame('01', $large->getContentType());
        $this->assertSame('https://images.example/large.jpg', $large->getUrl());
        $this->assertSame(400, $large->getHeight());
        $this->assertSame(400, $large->getWidth());
        $this->assertSame(40000, $large->getBytes());
        $this->assertSame('large.jpg', $large->getFilename());
        $this->assertSame('D502', $large->getImageFormat());
        $this->assertSame('20250404', $large->getContentDate('17'));
        $this->assertSame('20250404', $large->getLastUpdated());
        $this->assertNull($large->getContentDate('02'));
        $this->assertSame([['role' => '17', 'format' => '00', 'date' => '20250404']], $large->getContentDates());
    }

    public function testContentDateAsElementFormat(): void
    {
        $p = F::fromInner('<CollateralDetail>' . $this->resource('01', '400', 1, 'a', '<ContentDate><ContentDateRole>17</ContentDateRole><DateFormat>00</DateFormat><Date>20240101</Date></ContentDate>') . '</CollateralDetail>');
        $this->assertSame('20240101', $p->getResourceVersions()[0]->getContentDate('17'));
    }

    public function testFilterByContentType(): void
    {
        $this->assertCount(4, $this->covers()->getResourceVersions('01'));
        $this->assertCount(1, $this->covers()->getResourceVersions('15'));
        $this->assertCount(0, $this->covers()->getResourceVersions('99'));
    }

    public function testPickTheFourHundredPixelCover(): void
    {
        $v = $this->covers()->getFrontCoverNear(400);
        $this->assertSame('https://images.example/large.jpg', $v->getUrl());
        $this->assertSame('https://images.example/full.jpg', $this->covers()->getFrontCoverNear(1000)->getUrl());
        $this->assertSame('https://images.example/thumb.jpg', $this->covers()->getFrontCoverNear(10)->getUrl());
        $this->assertNull(F::fromInner('')->getFrontCoverNear());
    }

    public function testSeveralVersionsInOneSupportingResourceAreAllKept(): void
    {
        $p = F::fromInner('<CollateralDetail><SupportingResource><ResourceContentType>01</ResourceContentType><ResourceMode>03</ResourceMode>'
            . '<ResourceVersion><ResourceForm>01</ResourceForm><ResourceVersionFeature><ResourceVersionFeatureType>02</ResourceVersionFeatureType><FeatureValue>100</FeatureValue></ResourceVersionFeature><ResourceLink>https://x/1.jpg</ResourceLink></ResourceVersion>'
            . '<ResourceVersion><ResourceForm>01</ResourceForm><ResourceVersionFeature><ResourceVersionFeatureType>02</ResourceVersionFeatureType><FeatureValue>200</FeatureValue></ResourceVersionFeature><ResourceLink>https://x/2.jpg</ResourceLink></ResourceVersion>'
            . '</SupportingResource></CollateralDetail>');
        $this->assertSame(['https://x/1.jpg', 'https://x/2.jpg'], array_map(function ($v) {
            return $v->getUrl();
        }, $p->getResourceVersions()));
    }

    public function testRecordWithoutACoverInBlock2(): void
    {
        // a CollateralDetail with text only: no resource versions, no cover
        $p = F::fromInner('<CollateralDetail><TextContent><TextType>03</TextType><Text>Hi</Text></TextContent></CollateralDetail>');
        $this->assertSame([], $p->getResourceVersions());
        $this->assertNull($p->getFrontCoverNear());
        $this->assertSame([], $p->getImages());
    }

    // ---- hardening

    public function testNoDeprecationWhenConstructingTheParsers(): void
    {
        $messages = [];
        set_error_handler(function ($no, $str) use (&$messages) {
            $messages[] = $str;
            return true;
        });
        try {
            new OnixParser();
            $file = OnixFile::create(1);
            new ChunkOnixParser($file);
            new ChunkOnixParser($file, null, null);
            @unlink($file);
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $messages);
    }

    public function testMissingFileGivesACleanException(): void
    {
        $messages = [];
        set_error_handler(function ($no, $str) use (&$messages) {
            $messages[] = $str;
            return true;
        });
        try {
            new ChunkOnixParser('/nonexistent/file.xml');
            $this->fail('expected exception');
        } catch (\Exception $e) {
            $this->assertStringContainsString('File not found', $e->getMessage());
        } finally {
            restore_error_handler();
        }
        $this->assertSame([], $messages, 'no warning from filesize() on a missing file');
    }

    public function testFragmentWithOwnNamespaceAndExtraAttributesStillParses(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'onixns');
        file_put_contents($file, '<?xml version="1.0"?><ONIXMessage release="3.0" xmlns="' . F::NS . '">'
            . '<Product datestamp="20261026"><RecordReference>a.1</RecordReference><NotificationType>03</NotificationType>'
            . '<ProductIdentifier><ProductIDType>03</ProductIDType><IDValue>9782091575377</IDValue></ProductIdentifier>'
            . '<ProductSupply><SupplyDetail><ProductAvailability>20</ProductAvailability></SupplyDetail></ProductSupply></Product></ONIXMessage>');
        $got = [];
        (new ChunkOnixParser($file, null, new Logger()))->parseWithCheckpoints(function ($p) use (&$got) {
            $got[] = [$p->getRecordReference(), $p->getEan(), $p->getAvailabilityCode(), $p->getPresentBlocks()];
            return true;
        });
        @unlink($file);
        $this->assertSame([['a.1', '9782091575377', '20', ['ProductSupply']]], $got);
    }
}
