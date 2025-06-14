<?php

namespace ONIXParser\Model;

/**
 * ONIX Product Model
 * 
 * Represents a product in an ONIX message
 */
class Product
{
    /**
     * @var string Record reference (unique identifier within the ONIX message)
     */
    private $recordReference;
    
    /**
     * @var string Notification type code
     */
    private $notificationType;
    
    /**
     * @var string Notification type name
     */
    private $notificationTypeName;
    
    /**
     * @var string Deletion text (for deletion notifications)
     */
    private $deletionText;
    
    /**
     * @var string ISBN-13
     */
    private $isbn;
    
    /**
     * @var string EAN/GTIN-13
     */
    private $ean;
    
    /**
     * @var Title Title information
     */
    private $title;

    /**
     * @var string Global Location Number (GLN) of the supplier
     */
    private $supplierGLN;

    /**
     * @var string Product form code
     */
    private $productForm;

    /**
     * @var string Product form name
     */
    private $productFormName;
    
    /**
     * @var array<Subject> Subjects associated with the product
     */
    private $subjects = [];

    /**
     * @var array<Description> Collection of descriptions
     */
    private $descriptions = [];

    /**
     * @var array<Subject> CLIL subjects
     */
    private $clilSubjects = [];

    /**
     * @var array<Subject> THEMA subjects
     */
    private $themaSubjects = [];

    /**
     * @var array<Subject> ScoLOMFR subjects
     */
    private $scoLOMFRSubjects = [];

    /**
     * @var array<Image> Images and resources associated with the product
     */
    private $images = [];

    /**
     * @var array<Collection> Collections associated with the product
     */
    private $collections = [];
    
    /**
     * @var string Supplier name
     */
    private $supplierName;
    
    /**
     * @var string Supplier role code
     */
    private $supplierRole;
    
    /**
     * @var string Availability code
     */
    private $availabilityCode;
    
    /**
     * @var bool Whether the product is available
     */
    private $available = false;
    
    /**
     * @var array<Price> Prices
     */
    private $prices = [];
    
    /**
     * @var \SimpleXMLElement Original XML
     */
    private $xml;
    
    /**
     * @var string Publisher name (derived from supplier or imprint)
     */
    private $publisherName;
    
    /**
     * @var string Publication date
     */
    private $publicationDate;
    
    /**
     * @var string Availability date
     */
    private $availabilityDate;
    
    /**
     * @var string Announcement date
     */
    private $announcementDate;
    
    /**
     * @var array<Contributor> Contributors (authors, editors, etc.)
     */
    private $contributors = [];

    /**
     * Get record reference
     * 
     * @return string|null
     */
    public function getRecordReference()
    {
        return $this->recordReference;
    }

    /**
     * Set record reference
     * 
     * @param string $recordReference
     * @return self
     */
    public function setRecordReference($recordReference)
    {
        $this->recordReference = $recordReference;
        return $this;
    }

    /**
     * Get notification type code
     * 
     * @return string|null
     */
    public function getNotificationType()
    {
        return $this->notificationType;
    }

    /**
     * Set notification type code
     * 
     * @param string $notificationType
     * @return self
     */
    public function setNotificationType($notificationType)
    {
        $this->notificationType = $notificationType;
        return $this;
    }

    /**
     * Get notification type name
     * 
     * @return string|null
     */
    public function getNotificationTypeName()
    {
        return $this->notificationTypeName;
    }

    /**
     * Set notification type name
     * 
     * @param string $notificationTypeName
     * @return self
     */
    public function setNotificationTypeName($notificationTypeName)
    {
        $this->notificationTypeName = $notificationTypeName;
        return $this;
    }

    /**
     * Get deletion text
     * 
     * @return string|null
     */
    public function getDeletionText()
    {
        return $this->deletionText;
    }

    /**
     * Set deletion text
     * 
     * @param string $deletionText
     * @return self
     */
    public function setDeletionText($deletionText)
    {
        $this->deletionText = $deletionText;
        return $this;
    }

    /**
     * Get ISBN-13
     * 
     * @return string|null
     */
    public function getIsbn()
    {
        return $this->isbn;
    }

    /**
     * Set ISBN-13
     * 
     * @param string $isbn
     * @return self
     */
    public function setIsbn($isbn)
    {
        $this->isbn = $isbn;
        return $this;
    }

    /**
     * Get EAN/GTIN-13
     * 
     * @return string|null
     */
    public function getEan()
    {
        return $this->ean;
    }

    /**
     * Set EAN/GTIN-13
     * 
     * @param string $ean
     * @return self
     */
    public function setEan($ean)
    {
        $this->ean = $ean;
        return $this;
    }

    /**
     * Get title information
     * 
     * @return Title|null
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set title information
     * 
     * @param Title $title
     * @return self
     */
    public function setTitle(Title $title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get supplier name
     * 
     * @return string|null
     */
    public function getSupplierName()
    {
        return $this->supplierName;
    }

    /**
     * Set supplier name
     * 
     * @param string $supplierName
     * @return self
     */
    public function setSupplierName($supplierName)
    {
        $this->supplierName = $supplierName;
        return $this;
    }

    /**
     * Get supplier role code
     * 
     * @return string|null
     */
    public function getSupplierRole()
    {
        return $this->supplierRole;
    }

    /**
     * Set supplier role code
     * 
     * @param string $supplierRole
     * @return self
     */
    public function setSupplierRole($supplierRole)
    {
        $this->supplierRole = $supplierRole;
        return $this;
    }

    /**
     * Get availability code
     * 
     * @return string|null
     */
    public function getAvailabilityCode()
    {
        return $this->availabilityCode;
    }

    /**
     * Set availability code
     * 
     * @param string $availabilityCode
     * @return self
     */
    public function setAvailabilityCode($availabilityCode)
    {
        $this->availabilityCode = $availabilityCode;
        return $this;
    }

    /**
     * Check if product is available
     * 
     * @return bool
     */
    public function isAvailable()
    {
        return $this->available;
    }

    /**
     * Set product availability
     * 
     * @param bool $available
     * @return self
     */
    public function setAvailable($available)
    {
        $this->available = $available;
        return $this;
    }

    /**
     * Get all prices
     * 
     * @return array<Price>
     */
    public function getPrices()
    {
        return $this->prices;
    }

    /**
     * Add a price
     * 
     * @param Price $price
     * @return self
     */
    public function addPrice(Price $price)
    {
        $this->prices[] = $price;
        return $this;
    }

    /**
     * Get original XML as string
     * 
     * @return string|null
     */
    public function getXml()
    {
        if (!empty($this->xml)) {
            return $this->xml->asXML();
        }

        return null;
    }

    /**
     * Set original XML
     * 
     * @param \SimpleXMLElement $xml
     * @return self
     */
    public function setXml($xml)
    {
        $this->xml = $xml;
        return $this;
    }
    
    /**
     * Get default price
     * 
     * @return Price|null
     */
    public function getDefaultPrice()
    {
        if (empty($this->prices)) {
            return null;
        }
        
        // Return first price by default
        return $this->prices[0];
    }
    
    /**
     * Get title text
     * 
     * @return string|null
     */
    public function getTitleText()
    {
        return $this->title ? $this->title->getText() : null;
    }
    
    /**
     * Get subtitle
     * 
     * @return string|null
     */
    public function getSubtitle()
    {
        return $this->title ? $this->title->getSubtitle() : null;
    }

    /**
     * Add a description
     * 
     * @param Description $description
     * @return self
     */
    public function addDescription(Description $description)
    {
        $this->descriptions[] = $description;
        return $this;
    }

    /**
     * Get all descriptions
     * 
     * @return array<Description>
     */
    public function getDescriptions(): array
    {
        return $this->descriptions;
    }

    /**
     * Get descriptions by type
     * 
     * @param string $type Description type code
     * @return array<Description>
     */
    public function getDescriptionsByType(string $type): array
    {
        return array_filter($this->descriptions, function(Description $description) use ($type) {
            return $description->getType() === $type;
        });
    }

    /**
     * Get main description
     * 
     * @return Description|null
     */
    public function getMainDescription(): ?Description
    {
        foreach ($this->descriptions as $description) {
            if ($description->isMainDescription()) {
                return $description;
            }
        }
        return null;
    }

    /**
     * Get short description
     * 
     * @return Description|null
     */
    public function getShortDescription(): ?Description
    {
        foreach ($this->descriptions as $description) {
            if ($description->isShortDescription()) {
                return $description;
            }
        }
        return null;
    }

    /**
     * Get long description
     * 
     * @return Description|null
     */
    public function getLongDescription(): ?Description
    {
        foreach ($this->descriptions as $description) {
            if ($description->isLongDescription()) {
                return $description;
            }
        }
        return null;
    }

    /**
     * Get table of contents
     * 
     * @return Description|null
     */
    public function getTableOfContents(): ?Description
    {
        foreach ($this->descriptions as $description) {
            if ($description->isTableOfContents()) {
                return $description;
            }
        }
        return null;
    }

    /**
     * Get review quotes
     * 
     * @return array<Description>
     */
    public function getReviewQuotes(): array
    {
        return array_filter($this->descriptions, function(Description $description) {
            return $description->isReviewQuote();
        });
    }

    /**
     * Check if product has a specific description type
     * 
     * @param string $type Description type code
     * @return bool
     */
    public function hasDescriptionType(string $type): bool
    {
        foreach ($this->descriptions as $description) {
            if ($description->getType() === $type) {
                return true;
            }
        }
        return false;
    }


    /**
     * Get all subjects
     * 
     * @return array<Subject>
     */
    public function getSubjects()
    {
        return $this->subjects;
    }

    /**
     * Add a subject
     * 
     * @param Subject $subject
     * @return self
     */
    public function addSubject(Subject $subject)
    {
        $this->subjects[] = $subject;
        
        // Add to the appropriate category based on scheme
        if ($subject->isClil()) {
            $this->clilSubjects[] = $subject;
        } elseif ($subject->isThema()) {
            $this->themaSubjects[] = $subject;
        } elseif ($subject->isScoLOMFR()) {
            $this->scoLOMFRSubjects[] = $subject;
        }
        
        return $this;
    }

        /**
         * Get CLIL subjects
         * 
         * @return array<Subject>
         */
        public function getClilSubjects()
        {
            return $this->clilSubjects;
        }

        /**
         * Get THEMA subjects
         * 
         * @return array<Subject>
         */
        public function getThemaSubjects()
        {
            return $this->themaSubjects;
        }

        /**
         * Get ScoLOMFR subjects
         * 
         * @return array<Subject>
         */
        public function getScoLOMFRSubjects()
        {
            return $this->scoLOMFRSubjects;
        }

        /**
         * Get main subject
         * 
         * @param string|null $scheme Optional scheme to filter by ('29' for CLIL, '93' for THEMA, etc.)
         * @return Subject|null
         */
        public function getMainSubject($scheme = null)
        {
            foreach ($this->subjects as $subject) {
                if ($subject->isMainSubject() && 
                    ($scheme === null || $subject->getScheme() === $scheme)) {
                    return $subject;
                }
            }
            
            return null;
        }

        /**
         * Check if the product has a specific subject code
         * 
         * @param string $code Subject code to check
         * @param string|null $scheme Optional scheme to filter by ('29' for CLIL, '93' for THEMA, etc.)
         * @return bool
         */
        public function hasSubjectCode($code, $scheme = null)
        {
            foreach ($this->subjects as $subject) {
                if ($subject->getCode() === $code && 
                    ($scheme === null || $subject->getScheme() === $scheme)) {
                    return true;
                }
            }
            
            return false;
        }


        /**
         * Get all images
         * 
         * @return array<Image>
         */
        public function getImages()
        {
            return $this->images;
        }
        
        /**
         * Add an image
         * 
         * @param Image $image
         * @return self
         */
        public function addImage(Image $image)
        {
            $this->images[] = $image;
            return $this;
        }
        
        /**
         * Get cover images only
         * 
         * @return array<Image>
         */
        public function getCoverImages()
        {
            return array_filter($this->images, function($image) {
                return $image->isCoverImage();
            });
        }
        
        /**
         * Get primary cover image
         * 
         * @return Image|null
         */
        public function getPrimaryCoverImage()
        {
            $coverImages = $this->getCoverImages();
            return !empty($coverImages) ? reset($coverImages) : null;
        }
        
        /**
         * Get sample content images/resources
         * 
         * @return array<Image>
         */
        public function getSampleContent()
        {
            return array_filter($this->images, function($image) {
                return $image->isSampleContent();
            });
        }
        
        /**
         * Get images by type
         * 
         * @param string $contentType Content type code
         * @return array<Image>
         */
        public function getImagesByType($contentType)
        {
            return array_filter($this->images, function($image) use ($contentType) {
                return $image->getContentType() === $contentType;
            });
        }

        /**
         * Get all collections
         * 
         * @return array<Collection>
         */
        public function getCollections()
        {
            return $this->collections;
        }

        /**
         * Add a collection
         * 
         * @param Collection $collection
         * @return self
         */
        public function addCollection(Collection $collection)
        {
            $this->collections[] = $collection;
            return $this;
        }

        /**
         * Get series collections only
         * 
         * @return array<Collection>
         */
        public function getSeries()
        {
            return array_filter($this->collections, function($collection) {
                return $collection->isSeries();
            });
        }

        /**
         * Get regular collections only (not series)
         * 
         * @return array<Collection>
         */
        public function getRegularCollections()
        {
            return array_filter($this->collections, function($collection) {
                return $collection->isCollection();
            });
        }

        /**
         * Check if product is part of a series
         * 
         * @return bool
         */
        public function isPartOfSeries()
        {
            foreach ($this->collections as $collection) {
                if ($collection->isSeries()) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Check if product is part of a collection
         * 
         * @return bool
         */
        public function isPartOfCollection()
        {
            foreach ($this->collections as $collection) {
                if ($collection->isCollection()) {
                    return true;
                }
            }
            return false;
        }

        /**
         * Get primary series (usually the first one)
         * 
         * @return Collection|null
         */
        public function getPrimarySeries()
        {
            $series = $this->getSeries();
            return !empty($series) ? reset($series) : null;
        }

        /**
         * Get primary collection (usually the first one)
         * 
         * @return Collection|null
         */
        public function getPrimaryCollection()
        {
            $collections = $this->getRegularCollections();
            return !empty($collections) ? reset($collections) : null;
        }


    /**
     * Get supplier GLN
     * 
     * @return string|null
     */
    public function getSupplierGLN()
    {
        return $this->supplierGLN;
    }

    /**
     * Set supplier GLN
     * 
     * @param string $supplierGLN
     * @return self
     */
    public function setSupplierGLN($supplierGLN)
    {
        $this->supplierGLN = $supplierGLN;
        return $this;
    }

    /**
     * Get product form code
     * 
     * @return string|null
     */
    public function getProductForm()
    {
        return $this->productForm;
    }

    /**
     * Set product form code
     * 
     * @param string $productForm
     * @return self
     */
    public function setProductForm($productForm)
    {
        $this->productForm = $productForm;
        return $this;
    }

    /**
     * Get product form name
     * 
     * @return string|null
     */
    public function getProductFormName()
    {
        return $this->productFormName;
    }

    /**
     * Set product form name
     * 
     * @param string $productFormName
     * @return self
     */
    public function setProductFormName($productFormName)
    {
        $this->productFormName = $productFormName;
        return $this;
    }

    /**
     * Check if product is a book
     * 
     * @return bool
     */
    public function isBook()
    {
        // Book forms typically start with 'B'
        return $this->productForm && (substr($this->productForm, 0, 1) === 'B' || $this->productForm === '00');
    }
    
    /**
     * Get publisher name
     * 
     * @return string|null
     */
    public function getPublisherName()
    {
        return $this->publisherName ?: $this->supplierName;
    }

    /**
     * Set publisher name
     * 
     * @param string $publisherName
     * @return self
     */
    public function setPublisherName($publisherName)
    {
        $this->publisherName = $publisherName;
        return $this;
    }

    /**
     * Get publication date
     * 
     * @return string|null
     */
    public function getPublicationDate()
    {
        return $this->publicationDate;
    }

    /**
     * Set publication date
     * 
     * @param string $publicationDate
     * @return self
     */
    public function setPublicationDate($publicationDate)
    {
        $this->publicationDate = $publicationDate;
        return $this;
    }

    /**
     * Get availability date
     * 
     * @return string|null
     */
    public function getAvailabilityDate()
    {
        return $this->availabilityDate;
    }

    /**
     * Set availability date
     * 
     * @param string $availabilityDate
     * @return self
     */
    public function setAvailabilityDate($availabilityDate)
    {
        $this->availabilityDate = $availabilityDate;
        return $this;
    }

    /**
     * Get announcement date
     * 
     * @return string|null
     */
    public function getAnnouncementDate()
    {
        return $this->announcementDate;
    }

    /**
     * Set announcement date
     * 
     * @param string $announcementDate
     * @return self
     */
    public function setAnnouncementDate($announcementDate)
    {
        $this->announcementDate = $announcementDate;
        return $this;
    }

    /**
     * Get contributors
     * 
     * @return array
     */
    public function getContributors()
    {
        return $this->contributors;
    }

    /**
     * Set contributors
     * 
     * @param array $contributors
     * @return self
     */
    public function setContributors($contributors)
    {
        $this->contributors = $contributors;
        return $this;
    }

    /**
     * Add contributor
     * 
     * @param mixed $contributor
     * @return self
     */
    public function addContributor($contributor)
    {
        $this->contributors[] = $contributor;
        return $this;
    }

    /**
     * Get availability name using CodeMaps
     * 
     * @return string|null Human-readable availability name
     */
    public function getAvailabilityName()
    {
        if (!$this->availabilityCode) {
            return null;
        }
        
        $availabilityMap = \ONIXParser\CodeMaps::getAvailabilityCodeMap();
        return $availabilityMap[$this->availabilityCode] ?? null;
    }

    /**
     * Get page count from product extent information
     * Uses FieldMappings for XPath queries
     * 
     * @return int|null Number of pages
     */
    public function getPageCount()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $extentPaths = $mappings['physical']['extents'];
        
        // Find extent nodes
        $extents = $this->xml->xpath($extentPaths[0]) ?: $this->xml->xpath($extentPaths[1]);
        
        if ($extents) {
            $typePaths = $mappings['physical']['extent_type'];
            $valuePaths = $mappings['physical']['extent_value'];
            
            foreach ($extents as $extent) {
                $type = $extent->xpath($typePaths[0]) ?: $extent->xpath($typePaths[1]);
                $type = $type ? (string)$type[0] : null;
                
                // ExtentType 00 = Main content page count, 07 = Total numbered pages
                if ($type === '00' || $type === '07') {
                    $value = $extent->xpath($valuePaths[0]) ?: $extent->xpath($valuePaths[1]);
                    return $value ? (int)$value[0] : null;
                }
            }
        }
        
        return null;
    }

    /**
     * Get primary language code
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null ISO 639 language code
     */
    public function getLanguageCode()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        
        // Try primary language first
        $primaryPaths = $mappings['language']['primary'];
        $languages = $this->xml->xpath($primaryPaths[0]) ?: $this->xml->xpath($primaryPaths[1]);
        
        if ($languages && !empty($languages)) {
            return (string)$languages[0];
        }
        
        // Fallback to any language code
        $codePaths = $mappings['language']['code'];
        $languageNodes = $this->xml->xpath($mappings['language']['nodes'][0]) ?: 
                        $this->xml->xpath($mappings['language']['nodes'][1]);
        
        if ($languageNodes) {
            foreach ($languageNodes as $node) {
                $code = $node->xpath($codePaths[0]) ?: $node->xpath($codePaths[1]);
                if ($code) {
                    return (string)$code[0];
                }
            }
        }
        
        return null;
    }

    /**
     * Get primary language name using CodeMaps
     * 
     * @return string|null Human-readable language name
     */
    public function getLanguageName()
    {
        $languageCode = $this->getLanguageCode();
        if (!$languageCode) {
            return null;
        }
        
        $languageMap = \ONIXParser\CodeMaps::getLanguageCodeMap();
        return $languageMap[strtolower($languageCode)] ?? $languageCode;
    }

    /**
     * Get product form detail code
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null Product form detail code
     */
    public function getProductFormDetail()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $detailPaths = $mappings['product_form']['form_detail'];
        
        $details = $this->xml->xpath($detailPaths[0]) ?: $this->xml->xpath($detailPaths[1]);
        return $details && !empty($details) ? (string)$details[0] : null;
    }

    /**
     * Get product form detail name using CodeMaps
     * 
     * @return string|null Human-readable product form detail name
     */
    public function getProductFormDetailName()
    {
        $formDetail = $this->getProductFormDetail();
        if (!$formDetail) {
            return null;
        }
        
        $detailMap = \ONIXParser\CodeMaps::getProductFormDetailMap();
        return $detailMap[$formDetail] ?? null;
    }

    /**
     * Get imprint name
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null Imprint name
     */
    public function getImprintName()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $imprintPaths = $mappings['publisher']['imprint_name'];
        
        $imprints = $this->xml->xpath($imprintPaths[0]) ?: $this->xml->xpath($imprintPaths[1]);
        return $imprints && !empty($imprints) ? (string)$imprints[0] : null;
    }

    /**
     * Get height measurement
     * 
     * @return array|null Array with 'value' and 'unit' keys
     */
    public function getHeight()
    {
        return $this->getMeasurement('01'); // Height
    }

    /**
     * Get width measurement
     * 
     * @return array|null Array with 'value' and 'unit' keys
     */
    public function getWidth()
    {
        return $this->getMeasurement('02'); // Width
    }

    /**
     * Get thickness measurement
     * 
     * @return array|null Array with 'value' and 'unit' keys
     */
    public function getThickness()
    {
        return $this->getMeasurement('03'); // Thickness
    }

    /**
     * Get weight measurement
     * 
     * @return array|null Array with 'value' and 'unit' keys
     */
    public function getWeight()
    {
        return $this->getMeasurement('08'); // Weight
    }

    /**
     * Get measurement by type using FieldMappings
     * 
     * @param string $measureType ONIX measure type code
     * @return array|null Array with 'value', 'unit', and 'unit_name' keys
     */
    private function getMeasurement($measureType)
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $measurePaths = $mappings['physical']['measures'];
        
        // Find measure nodes
        $measures = $this->xml->xpath($measurePaths[0]) ?: $this->xml->xpath($measurePaths[1]);
        
        if ($measures) {
            $typePaths = $mappings['physical']['measure_type'];
            $valuePaths = $mappings['physical']['measure_value'];
            $unitPaths = $mappings['physical']['measure_unit'];
            
            foreach ($measures as $measure) {
                $type = $measure->xpath($typePaths[0]) ?: $measure->xpath($typePaths[1]);
                $type = $type ? (string)$type[0] : null;
                
                if ($type === $measureType) {
                    $value = $measure->xpath($valuePaths[0]) ?: $measure->xpath($valuePaths[1]);
                    $unit = $measure->xpath($unitPaths[0]) ?: $measure->xpath($unitPaths[1]);
                    
                    if ($value) {
                        $unitCode = $unit ? (string)$unit[0] : null;
                        $unitMap = \ONIXParser\CodeMaps::getMeasureUnitMap();
                        
                        return [
                            'value' => (float)$value[0],
                            'unit' => $unitCode,
                            'unit_name' => $unitCode ? ($unitMap[$unitCode] ?? $unitCode) : null
                        ];
                    }
                }
            }
        }
        
        return null;
    }

    /**
     * Get country of publication
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null Country code
     */
    public function getCountryOfPublication()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $countryPaths = $mappings['publishing_metadata']['country_of_publication'];
        
        $countries = $this->xml->xpath($countryPaths[0]) ?: $this->xml->xpath($countryPaths[1]);
        return $countries && !empty($countries) ? (string)$countries[0] : null;
    }

    /**
     * Get country of publication name using CodeMaps
     * 
     * @return string|null Human-readable country name
     */
    public function getCountryOfPublicationName()
    {
        $countryCode = $this->getCountryOfPublication();
        if (!$countryCode) {
            return null;
        }
        
        $countryMap = \ONIXParser\CodeMaps::getCountryCodeMap();
        return $countryMap[$countryCode] ?? $countryCode;
    }

    /**
     * Get city of publication
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null City name
     */
    public function getCityOfPublication()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $cityPaths = $mappings['publishing_metadata']['city_of_publication'];
        
        $cities = $this->xml->xpath($cityPaths[0]) ?: $this->xml->xpath($cityPaths[1]);
        return $cities && !empty($cities) ? (string)$cities[0] : null;
    }

    /**
     * Get copyright year
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null Copyright year
     */
    public function getCopyrightYear()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $copyrightPaths = $mappings['publishing_metadata']['copyright_year'];
        
        $copyrights = $this->xml->xpath($copyrightPaths[0]) ?: $this->xml->xpath($copyrightPaths[1]);
        return $copyrights && !empty($copyrights) ? (string)$copyrights[0] : null;
    }

    /**
     * Get first publication year
     * Uses existing publication date functionality
     * 
     * @return string|null First publication year
     */
    public function getFirstPublicationYear()
    {
        $publicationDate = $this->getPublicationDate();
        if (!$publicationDate) {
            return null;
        }
        
        // Extract year from YYYYMMDD or YYYY format
        if (strlen($publicationDate) >= 4) {
            return substr($publicationDate, 0, 4);
        }
        
        return null;
    }

    /**
     * Get edition number
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null Edition number
     */
    public function getEditionNumber()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $editionPaths = $mappings['publishing_metadata']['edition_number'];
        
        $editions = $this->xml->xpath($editionPaths[0]) ?: $this->xml->xpath($editionPaths[1]);
        return $editions && !empty($editions) ? (string)$editions[0] : null;
    }

    /**
     * Get edition statement
     * Uses FieldMappings for XPath queries
     * 
     * @return string|null Edition statement
     */
    public function getEditionStatement()
    {
        if (!$this->xml) {
            return null;
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $statementPaths = $mappings['publishing_metadata']['edition_statement'];
        
        $statements = $this->xml->xpath($statementPaths[0]) ?: $this->xml->xpath($statementPaths[1]);
        return $statements && !empty($statements) ? (string)$statements[0] : null;
    }
}