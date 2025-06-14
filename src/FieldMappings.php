<?php

namespace ONIXParser;

/**
 * ONIX Field Mappings
 * 
 * Contains XPath mappings for ONIX fields to support both namespaced and non-namespaced XML.
 * Each mapping is an array with two values:
 * - First value: XPath for namespaced XML (with 'onix:' prefix)
 * - Second value: XPath for non-namespaced XML
 */
class FieldMappings
{
    /**
     * Get all field mappings
     * 
     * @return array
     */
    public static function getMappings(): array
    {
        return [
            // Header information
            'header' => [
                'node' => ['//onix:Header', '//Header'],
                'sender' => ['//onix:Header/onix:Sender/onix:SenderName', '//Header/Sender/SenderName'],
                'contact' => ['//onix:Header/onix:Sender/onix:ContactName', '//Header/Sender/ContactName'],
                'email' => ['//onix:Header/onix:Sender/onix:EmailAddress', '//Header/Sender/EmailAddress'],
                'sent_date' => ['//onix:Header/onix:SentDateTime', '//Header/SentDateTime'],
            ],
            
            // Product nodes
            'products' => ['//onix:Product', '//Product'],
            'record_reference' => ['./onix:RecordReference', './RecordReference'],
            
            // Basic product information
            'notification' => [
                'type' => ['./onix:NotificationType', './NotificationType'],
                'deletion_text' => ['./onix:DeletionText', './DeletionText'],
            ],
            
            // Identifiers
            'identifiers' => [
                'isbn' => [
                    ".//onix:ProductIdentifier[onix:ProductIDType='15']/onix:IDValue", 
                    ".//ProductIdentifier[ProductIDType='15']/IDValue"
                ],
                'ean' => [
                    ".//onix:ProductIdentifier[onix:ProductIDType='03']/onix:IDValue", 
                    ".//ProductIdentifier[ProductIDType='03']/IDValue"
                ],
                'gln' => [
                    './onix:ProductSupply/onix:SupplyDetail/onix:Supplier/onix:SupplierIdentifier[onix:SupplierIDType="06"]/onix:IDValue', 
                    './ProductSupply/SupplyDetail/Supplier/SupplierIdentifier[SupplierIDType="06"]/IDValue'
                ],
            ],
            
            // Title information
            'title' => [
                'main' => [
                    ".//onix:TitleDetail[onix:TitleType='01']/onix:TitleElement[onix:TitleElementLevel='01']/onix:TitleText", 
                    ".//TitleDetail[TitleType='01']/TitleElement[TitleElementLevel='01']/TitleText"
                ],
                'subtitle' => [
                    ".//onix:TitleDetail[onix:TitleType='01']/onix:TitleElement[onix:TitleElementLevel='01']/onix:Subtitle", 
                    ".//TitleDetail[TitleType='01']/TitleElement[TitleElementLevel='01']/Subtitle"
                ],
                'collection' => [
                    ".//onix:Collection[onix:CollectionType='11']/onix:TitleDetail/onix:TitleElement[onix:TitleElementLevel='02']/onix:TitleText", 
                    ".//Collection[CollectionType='11']/TitleDetail/TitleElement[TitleElementLevel='02']/TitleText"
                ],
                'series' => [
                    ".//onix:Collection[onix:CollectionType='10']/onix:TitleDetail/onix:TitleElement[onix:TitleElementLevel='02']/onix:TitleText", 
                    ".//Collection[CollectionType='10']/TitleDetail/TitleElement[TitleElementLevel='02']/TitleText"
                ],
            ],
            
            // Contributors
            'contributors' => ['./onix:DescriptiveDetail/onix:Contributor', './DescriptiveDetail/Contributor'],
            'no_contributor' => ['./onix:DescriptiveDetail/onix:NoContributor', './DescriptiveDetail/NoContributor'],
            'contributor_fields' => [
                'role' => ['./onix:ContributorRole', './ContributorRole'],
                'name' => ['./onix:PersonName', './PersonName'],
                'name_inverted' => ['./onix:PersonNameInverted', './PersonNameInverted'],
                'first_name' => ['./onix:NamesBeforeKey', './NamesBeforeKey'],
                'last_name' => ['./onix:KeyNames', './KeyNames'],
                'corporate_name' => ['./onix:CorporateName', './CorporateName'],
            ],
            
            // Collections and series
            'collections' => [
                'nodes' => ['./onix:DescriptiveDetail/onix:Collection', './DescriptiveDetail/Collection'],
                'type' => ['./onix:CollectionType', './CollectionType'],
                'title_details' => ['./onix:TitleDetail', './TitleDetail'],
                'title_type' => ['./onix:TitleType', './TitleType'],
                'title_elements' => ['./onix:TitleElement', './TitleElement'],
                'title_level' => ['./onix:TitleElementLevel', './TitleElementLevel'],
                'title_text' => ['./onix:TitleText', './TitleText'],
                'part_number' => ['./onix:PartNumber', './PartNumber'],
            ],
            'no_collection' => ['./onix:DescriptiveDetail/onix:NoCollection', './DescriptiveDetail/NoCollection'],
            
            // Product form
            'product_form' => [
                'form_code' => ['./onix:DescriptiveDetail/onix:ProductForm', './DescriptiveDetail/ProductForm'],
                'form_detail' => ['./onix:DescriptiveDetail/onix:ProductFormDetail', './DescriptiveDetail/ProductFormDetail'],
                'epublication_type' => ['./onix:DescriptiveDetail/onix:EpubType', './DescriptiveDetail/EpubType'],
            ],
            
            // Descriptions
            'description' => [
                'text_nodes' => ['./onix:CollateralDetail/onix:TextContent', './CollateralDetail/TextContent'],
                'text_type' => ['./onix:TextType', './TextType'],
                'text_format' => ['./onix:TextFormat', './TextFormat'],
                'text_content' => ['./onix:Text', './Text'],
            ],
            
            // Physical measurements
            'physical' => [
                'measures' => ['./onix:DescriptiveDetail/onix:Measure', './DescriptiveDetail/Measure'],
                'measure_type' => ['./onix:MeasureType', './MeasureType'],
                'measure_value' => ['./onix:Measurement', './Measurement'],
                'measure_unit' => ['./onix:MeasureUnitCode', './MeasureUnitCode'],
                'extents' => ['./onix:DescriptiveDetail/onix:Extent', './DescriptiveDetail/Extent'],
                'extent_type' => ['./onix:ExtentType', './ExtentType'],
                'extent_value' => ['./onix:ExtentValue', './ExtentValue'],
                'extent_unit' => ['./onix:ExtentUnit', './ExtentUnit'],
            ],
            
            // Subjects
            'subjects' => [
                'nodes' => ['./onix:DescriptiveDetail/onix:Subject', './DescriptiveDetail/Subject'],
                'main_subject' => ['./onix:MainSubject', './MainSubject'],
                'scheme_identifier' => ['./onix:SubjectSchemeIdentifier', './SubjectSchemeIdentifier'],
                'code' => ['./onix:SubjectCode', './SubjectCode'],
                'heading_text' => ['./onix:SubjectHeadingText', './SubjectHeadingText'],
            ],
            
            // Publisher information
            'publisher' => [
                'publisher_role' => [
                    './onix:PublishingDetail/onix:Publisher/onix:PublishingRole',
                    './PublishingDetail/Publisher/PublishingRole'
                ],
                'publisher_name' => [
                    './onix:PublishingDetail/onix:Publisher/onix:PublisherName',
                    './PublishingDetail/Publisher/PublisherName'
                ],
                'imprint_name' => [
                    './onix:PublishingDetail/onix:Imprint/onix:ImprintName',
                    './PublishingDetail/Imprint/ImprintName'
                ],
            ],
            
            // Publication dates
            'publication_dates' => [
                'nodes' => ['./onix:PublishingDetail/onix:PublishingDate', './PublishingDetail/PublishingDate'],
                'role' => ['./onix:PublishingDateRole', './PublishingDateRole'],
                'format' => ['./onix:DateFormat', './DateFormat'],
                'date' => ['./onix:Date', './Date'],
                'publication' => [
                    ".//onix:PublishingDate[onix:PublishingDateRole='01']/onix:Date", 
                    ".//PublishingDate[PublishingDateRole='01']/Date"
                ],
                'embargo' => [
                    ".//onix:PublishingDate[onix:PublishingDateRole='02']/onix:Date", 
                    ".//PublishingDate[PublishingDateRole='02']/Date"
                ],
            ],
            
            // Supply chain info
            'supply' => [
                'supply_details' => ['./onix:ProductSupply/onix:SupplyDetail', './ProductSupply/SupplyDetail'],
                'supplier_name' => ['./onix:Supplier/onix:SupplierName', './Supplier/SupplierName'],
                'supplier_role' => ['./onix:Supplier/onix:SupplierRole', './Supplier/SupplierRole'],
                'gln' => ['./onix:Supplier/onix:SupplierIdentifier[onix:SupplierIDType="06"]/onix:IDValue', './Supplier/SupplierIdentifier[SupplierIDType="06"]/IDValue'],
            ],
            
            // Product availability
            'stock' => [
                'availability' => [
                    './onix:ProductSupply/onix:SupplyDetail/onix:ProductAvailability', 
                    './ProductSupply/SupplyDetail/ProductAvailability'
                ],
                'on_sale_date' => [
                    ".//onix:SupplyDate[onix:SupplyDateRole='02']/onix:Date",
                    ".//SupplyDate[SupplyDateRole='02']/Date"
                ],
            ],
            
            // Pricing information
            'pricing' => [
                'price_nodes' => ['./onix:Price', './Price'],
                'price_type' => ['./onix:PriceType', './PriceType'],
                'price_amount' => ['./onix:PriceAmount', './PriceAmount'],
                'currency_code' => ['./onix:CurrencyCode', './CurrencyCode'],
                'tax_type' => ['./onix:Tax/onix:TaxType', './Tax/TaxType'],
                'tax_rate_code' => ['./onix:Tax/onix:TaxRateCode', './Tax/TaxRateCode'],
                'tax_rate_percent' => ['./onix:Tax/onix:TaxRatePercent', './Tax/TaxRatePercent'],
                'unpriced_item_type' => ['./onix:UnpricedItemType', './UnpricedItemType'],
            ],
            
            // Images and resources
            'images' => [
                'nodes' => [
                    './/onix:CollateralDetail/onix:SupportingResource', 
                    './/CollateralDetail/SupportingResource'
                ],
                'content_type' => ['./onix:ResourceContentType', './ResourceContentType'],
                'mode' => ['./onix:ResourceMode', './ResourceMode'],
                'url' => ['.//onix:ResourceVersion/onix:ResourceLink', './/ResourceVersion/ResourceLink'],
            ],
            
            // Language information
            'language' => [
                'nodes' => ['./onix:DescriptiveDetail/onix:Language', './DescriptiveDetail/Language'],
                'role' => ['./onix:LanguageRole', './LanguageRole'],
                'code' => ['./onix:LanguageCode', './LanguageCode'],
                'primary' => [
                    ".//onix:Language[onix:LanguageRole='01']/onix:LanguageCode",
                    ".//Language[LanguageRole='01']/LanguageCode"
                ],
            ],
            
            // Publishing metadata
            'publishing_metadata' => [
                'country_of_publication' => [
                    './onix:PublishingDetail/onix:CountryOfPublication',
                    './PublishingDetail/CountryOfPublication'
                ],
                'city_of_publication' => [
                    './onix:PublishingDetail/onix:CityOfPublication',
                    './PublishingDetail/CityOfPublication'
                ],
                'copyright_year' => [
                    './/onix:CopyrightStatement/onix:CopyrightYear',
                    './/CopyrightStatement/CopyrightYear'
                ],
                'edition_number' => [
                    './onix:DescriptiveDetail/onix:EditionNumber',
                    './DescriptiveDetail/EditionNumber'
                ],
                'edition_statement' => [
                    './onix:DescriptiveDetail/onix:EditionStatement',
                    './DescriptiveDetail/EditionStatement'
                ],
            ],
        ];
    }
}