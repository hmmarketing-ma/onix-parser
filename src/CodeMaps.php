<?php

namespace ONIXParser;

/**
 * ONIX Code Maps
 * 
 * Contains mapping of ONIX codes to human-readable descriptions.
 */
class CodeMaps
{
    /**
     * Get product form codes (List 150)
     * 
     * @return array
     */
    public static function getProductFormMap(): array
    {
        return [
            'AA' => 'Audio (unspecified)',
            'AB' => 'Audio cassette',
            'AC' => 'Audio CD',
            'AD' => 'Audio DAT',
            'AE' => 'Audio disc',
            'AF' => 'Audio tape',
            'AG' => 'Audio CD-ROM',
            'AI' => 'Audio DVD',
            'AJ' => 'Audio download',
            'AK' => 'Audio streaming',
            'AL' => 'Audio MP3',
            'BA' => 'Book',
            'BB' => 'Hardback',
            'BC' => 'Paperback',
            'BD' => 'Loose-leaf',
            'BE' => 'Spiral bound',
            'BF' => 'Pamphlet',
            'BG' => 'Binding unknown',
            'BH' => 'Board book',
            'BI' => 'Leatherbound',
            'BJ' => 'Library binding',
            'BK' => 'Rag book',
            'BL' => 'Bath book',
            'BP' => 'Picture book',
            'BZ' => 'Other book format',
            'DA' => 'Digital (unspecified)',
            'DB' => 'Digital book',
            'DC' => 'Digital audiobook',
            'DD' => 'Digital document',
            'DE' => 'Digital resource',
            'DF' => 'Digital online resource',
            'DG' => 'Digital file',
            'DH' => 'Digital app',
            'DI' => 'Digital audio',
            'DJ' => 'Digital video',
            'DZ' => 'Other digital format',
            'EA' => 'E-book',
            'EB' => 'E-book (EPUB)',
            'EC' => 'E-book (Web-based)',
            'ED' => 'E-book (PDF)',
            'FA' => 'Film or transparency',
            'FC' => 'Slides',
            'FD' => 'OHP transparencies',
            'FE' => 'Filmstrip',
            'FF' => 'Film',
            'FZ' => 'Other film or transparency format',
            'VA' => 'Video (unspecified)',
            'VI' => 'DVD video',
            'VJ' => 'VHS video',
            'VK' => 'Blu-ray',
            'VZ' => 'Other video format',
            'XA' => 'General trade item',
            'XB' => 'Dumpbin – empty',
            'XC' => 'Toy',
            'XD' => 'Game',
            '00' => 'Undefined',
        ];
    }
    
    /**
     * Get notification type codes (List 1)
     * 
     * @return array
     */
    public static function getNotificationTypeMap(): array
    {
        return [
            '01' => 'early_notification',
            '02' => 'advanced_notification',
            '03' => 'confirmed_on_publication',
            '04' => 'update',
            '05' => 'delete'
        ];
    }
    
    /**
     * Get availability codes (List 65)
     * 
     * @return array
     */
    public static function getAvailabilityCodeMap(): array
    {
        return [
            '01' => 'cancelled',
            '09' => 'unavailable', // Not yet available, postponed indefinitely
            '10' => 'unavailable', // Not yet available
            '11' => 'unavailable', // Awaiting stock
            '12' => 'unavailable', // Not yet available, will be POD
            '20' => 'available',   // Available
            '21' => 'available',   // In stock
            '22' => 'available',   // To order
            '23' => 'available',   // POD
            '30' => 'unavailable', // Temporarily unavailable
            '31' => 'unavailable', // Out of stock
            '32' => 'unavailable', // Reprinting
            '33' => 'unavailable', // Awaiting reissue
            '34' => 'unavailable', // Temporarily withdrawn from sale
            '40' => 'unavailable', // Not available (unspecified)
            '41' => 'unavailable', // Replaced by new product
            '42' => 'unavailable', // Other format available
            '43' => 'unavailable', // No longer supplied by us
            '44' => 'unavailable', // Apply direct
            '45' => 'unavailable', // Not sold separately
            '46' => 'unavailable', // Withdrawn from sale
            '47' => 'unavailable', // Remaindered
            '48' => 'unavailable', // Not available, replaced by POD
            '49' => 'unavailable', // Recalled
            '97' => 'unavailable', // No recent update received
            '98' => 'unavailable', // No longer receiving updates
            '99' => 'unavailable', // Contact supplier
        ];
    }
    
    /**
     * Get contributor role codes (List 17)
     * 
     * @return array
     */
    public static function getContributorRoleMap(): array
    {
        return [
            'A01' => 'author',
            'A02' => 'co-author',
            'A03' => 'screenwriter',
            'A04' => 'lyricist',
            'A05' => 'composer',
            'A06' => 'illustrator',
            'A07' => 'editor',
            'A08' => 'translator',
            'A09' => 'creator',
            'A10' => 'publishing_director',
            'A11' => 'prepared_for_publication_by',
            'A12' => 'illustrator',
            'A13' => 'photographer',
            'A14' => 'foreword_by',
            'A15' => 'afterword_by',
            'A16' => 'preface_by',
            'A17' => 'contributor',
            'A18' => 'introduction_by',
            'A19' => 'software_by',
            'A20' => 'research_by',
            'A21' => 'notes_by',
            'A22' => 'other',
            'A24' => 'editorial_coordination_by',
            'A25' => 'scientific_director',
            'A26' => 'technical_director',
            'A27' => 'thesis_advisor',
            'A29' => 'editor_in_chief',
            'A30' => 'editorial_team',
            'A31' => 'collaborator',
            'A32' => 'contributor',
            'A99' => 'other_creator',
            'B01' => 'edited_by',
            'B02' => 'revised_by',
            'B03' => 'retold_by',
            'B06' => 'translated_by',
            'B09' => 'series_edited_by',
            'B10' => 'edited_and_translated_by',
            'B11' => 'editor_in_chief',
            'B12' => 'guest_editor',
            'B13' => 'volume_editor',
            'B14' => 'editorial_board_member',
            'B15' => 'editorial_coordination_by',
            'B16' => 'managing_editor',
            'B17' => 'founded_by',
            'B18' => 'prepared_for_publication_by',
            'B19' => 'associate_editor',
            'B20' => 'consultant_editor',
            'B21' => 'general_editor',
            'B99' => 'other_adaptation_by',
            'Z99' => 'other_involvement',
        ];
    }
    
    /**
     * Get text type codes (List 153)
     * 
     * @return array
     */
    public static function getTextTypeMap(): array
    {
        return [
            '01' => 'Main description',
            '02' => 'Short description',
            '03' => 'Long description',
            '04' => 'Table of contents',
            '05' => 'Review quote',
            '06' => 'Review',
            '07' => 'Cover blurb',
            '08' => 'Back cover copy',
            '09' => 'Endorsement',
            '10' => 'Promotional headline',
            '11' => 'Feature',
            '12' => 'Biographical note',
            '13' => 'Publisher\'s note',
            '14' => 'Excerpt',
            '15' => 'Index',
            '16' => 'Short description for children',
            '17' => 'Description for sales people',
            '18' => 'Description for press or reviewers',
            '19' => 'Description for educational purposes',
            '20' => 'Description for teachers',
            '23' => 'Version history note',
            '24' => 'Description for retailers',
        ];
    }

    /**
     * Get text format codes (List 34)
     * 
     * @return array
     */
    public static function getTextFormatMap(): array
    {
        return [
            '00' => 'ASCII text',
            '01' => 'SGML',
            '02' => 'HTML',
            '03' => 'XML',
            '04' => 'PDF',
            '05' => 'XHTML',
            '06' => 'Default text format',
            '07' => 'Basic ASCII text',
            '08' => 'PDF',
            '09' => 'Microsoft Word',
            '10' => 'Text',
            '11' => 'Web-ready',
            '12' => 'Microsoft Excel',
            '13' => 'Markdown'
        ];
    }
    
    /**
     * Get publishing date role codes (List 163)
     * 
     * @return array
     */
    public static function getPublishingDateRoleMap(): array
    {
        return [
            '01' => 'Publication date',
            '02' => 'Embargo date',
            '09' => 'Public announcement date',
            '10' => 'Trade announcement date',
            '11' => 'Date of first publication',
            '12' => 'Last reprint date',
            '13' => 'Out-of-print / deletion date',
            '16' => 'Last reissue date',
            '19' => 'Publication date of print counterpart',
            '20' => 'Date of first publication in original language',
            '21' => 'Forthcoming reissue date',
            '25' => 'Publisher\'s reservation order deadline',
            '26' => 'Forthcoming reprint date',
            '27' => 'Preorder embargo date',
        ];
    }

    /**
     * Collection type map (List 148)
     * 
     * @return array
     */
    public static function getCollectionTypeMap(): array
    {
        return [
            '10' => 'Series',
            '11' => 'Collection',
            '20' => 'Subseries'
        ];
    }
    
    /**
     * Get price type codes (List 58)
     * 
     * @return array
     */
    public static function getPriceTypeMap(): array
    {
        return [
            '01' => 'RRP excluding tax',
            '02' => 'RRP including tax',
            '03' => 'Fixed retail price excluding tax',
            '04' => 'Fixed retail price including tax',
            '05' => 'Suggested retail price excluding tax',
            '06' => 'Suggested retail price including tax',
            '07' => 'Supplier\'s net price excluding tax',
            '08' => 'Supplier\'s net price excluding tax: rental goods',
            '09' => 'Supplier\'s net price including tax',
            '11' => 'Special sale RRP excluding tax',
            '12' => 'Special sale RRP including tax',
            '13' => 'Special sale fixed retail price excluding tax',
            '14' => 'Special sale fixed retail price including tax',
            '15' => 'Supplier\'s net price for special sale excluding tax',
            '17' => 'Supplier\'s net price for special sale including tax',
            '21' => 'Pre-publication RRP excluding tax',
            '22' => 'Pre-publication RRP including tax',
            '23' => 'Pre-publication fixed retail price excluding tax',
            '24' => 'Pre-publication fixed retail price including tax',
            '25' => 'Supplier\'s pre-publication net price excluding tax',
            '27' => 'Supplier\'s pre-publication net price including tax',
            '31' => 'Freight-pass-through RRP excluding tax',
            '32' => 'Freight-pass-through billing price',
            '33' => 'Importer\'s fixed retail price excluding tax',
            '34' => 'Importer\'s fixed retail price including tax',
            '41' => 'Publishers retail price excluding tax',
            '42' => 'Publishers retail price including tax',
            '43' => 'Distributors\/retailers net price excluding tax',
            '44' => 'Distributors\/retailers net price including tax'
        ];
    }

    /**
     * Get product form detail codes (List 175)
     * 
     * @return array
     */
    public static function getProductFormDetailMap(): array
    {
        return [
            'A101' => 'Hardcover book with dust jacket',
            'A102' => 'Hardcover book without dust jacket',
            'A103' => 'Trade paperback (UK mass-market paperback)',
            'A104' => 'Mass market paperback',
            'A105' => 'Picture book',
            'A106' => 'Board book',
            'A107' => 'Novelty book',
            'A108' => 'Gift book',
            'A109' => 'Pop-up book',
            'A110' => 'Cloth book',
            'A111' => 'Bath book',
            'A112' => 'Book with toy or other items',
            'A201' => 'Audiobook',
            'A202' => 'Enhanced audiobook',
            'A301' => 'Basic ebook',
            'A302' => 'Enhanced ebook',
            'A303' => 'Fixed format ebook',
            'A304' => 'Ebook with multimedia',
            'A305' => 'Interactive ebook',
        ];
    }

    /**
     * Get language codes (ISO 639)
     * 
     * @return array
     */
    public static function getLanguageCodeMap(): array
    {
        return [
            'fre' => 'French',
            'fra' => 'French',
            'eng' => 'English',
            'spa' => 'Spanish',
            'ger' => 'German',
            'deu' => 'German',
            'ita' => 'Italian',
            'por' => 'Portuguese',
            'rus' => 'Russian',
            'jpn' => 'Japanese',
            'chi' => 'Chinese',
            'zho' => 'Chinese',
            'ara' => 'Arabic',
            'hin' => 'Hindi',
            'ben' => 'Bengali',
            'kor' => 'Korean',
            'tur' => 'Turkish',
            'vie' => 'Vietnamese',
            'pol' => 'Polish',
            'ukr' => 'Ukrainian',
            'nld' => 'Dutch',
            'swe' => 'Swedish',
            'nor' => 'Norwegian',
            'dan' => 'Danish',
            'fin' => 'Finnish',
            'heb' => 'Hebrew',
            'tha' => 'Thai',
            'msa' => 'Malay',
            'ind' => 'Indonesian',
        ];
    }

    /**
     * Get country codes (ISO 3166-1)
     * 
     * @return array
     */
    public static function getCountryCodeMap(): array
    {
        return [
            'FR' => 'France',
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'AU' => 'Australia',
            'DE' => 'Germany',
            'ES' => 'Spain',
            'IT' => 'Italy',
            'JP' => 'Japan',
            'CN' => 'China',
            'IN' => 'India',
            'BR' => 'Brazil',
            'RU' => 'Russia',
            'MX' => 'Mexico',
            'KR' => 'South Korea',
            'NL' => 'Netherlands',
            'SE' => 'Sweden',
            'NO' => 'Norway',
            'DK' => 'Denmark',
            'FI' => 'Finland',
            'PL' => 'Poland',
            'CH' => 'Switzerland',
            'AT' => 'Austria',
            'BE' => 'Belgium',
            'PT' => 'Portugal',
            'GR' => 'Greece',
            'TR' => 'Turkey',
            'CZ' => 'Czech Republic',
            'HU' => 'Hungary',
            'IE' => 'Ireland',
        ];
    }

    /**
     * Get measure unit codes (List 50)
     * 
     * @return array
     */
    public static function getMeasureUnitMap(): array
    {
        return [
            'mm' => 'Millimeters',
            'cm' => 'Centimeters',
            'in' => 'Inches',
            'ft' => 'Feet',
            'gr' => 'Grams',
            'kg' => 'Kilograms',
            'oz' => 'Ounces',
            'lb' => 'Pounds',
            'px' => 'Pixels',
        ];
    }
    
    /**
     * Get all code maps
     * 
     * @return array
     */
    public static function getAllMaps(): array
    {
        return [
            'product_form' => self::getProductFormMap(),
            'product_form_detail' => self::getProductFormDetailMap(),
            'notification_type' => self::getNotificationTypeMap(),
            'availability_code' => self::getAvailabilityCodeMap(),
            'contributor_role' => self::getContributorRoleMap(),
            'text_type' => self::getTextTypeMap(),
            'text_format' => self::getTextFormatMap(),
            'publishing_date_role' => self::getPublishingDateRoleMap(),
            'collection_type' => self::getCollectionTypeMap(),
            'price_type' => self::getPriceTypeMap(),
            'language_code' => self::getLanguageCodeMap(),
            'country_code' => self::getCountryCodeMap(),
            'measure_unit' => self::getMeasureUnitMap(),
            // List 30: Resource content type
            'resource_content_type' => [
                '01' => 'Front cover',
                '02' => 'Back cover',
                '03' => 'Cover / pack image',
                '04' => 'Contributor picture',
                '05' => 'Series image / artwork',
                '06' => 'Series logo',
                '07' => 'Product logo',
                '08' => 'Publisher logo',
                '09' => 'Imprint logo',
                '10' => 'Contributor interview',
                '11' => 'Contributor presentation',
                '12' => 'Contributor reading',
                '13' => 'Promotional audio',
                '14' => 'Promotional video',
                '15' => 'Sample content',
                '16' => 'Review',
                '17' => 'Other commentary / discussion',
                '18' => 'Reading group guide',
                '19' => 'Teacher\'s guide',
                '20' => 'Feature article',
                '21' => 'Icon or badge',
                '22' => 'Full content',
                '23' => 'Full content stream',
                '24' => 'Master brand logo',
                '25' => 'Master brand audio',
                '26' => 'Multi-item promotional piece',
                '27' => 'Detailed description',
                '28' => 'Lead-in material',
                '29' => 'Table of contents',
                '30' => 'Full cover',
                '31' => 'Back material',
                '32' => 'Full text',
                '33' => 'Widowed content',
            ],
            
            // List 31: Resource mode
            'resource_mode' => [
                '01' => 'Text',
                '02' => 'Audio',
                '03' => 'Image',
                '04' => 'Video',
                '05' => 'Multi-mode',
                '06' => 'Application',
                '07' => 'XML',
            ],
        ];
    }
}