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
     * @var SupplyDetail[] One object per <SupplyDetail> (supplier, availability, returns, dates, prices)
     */
    private $supplyDetails = [];

    /**
     * ONIX 3.0 block element names, in message order.
     */
    public const BLOCKS = ['DescriptiveDetail', 'CollateralDetail', 'ContentDetail', 'PublishingDetail', 'RelatedMaterial', 'ProductSupply'];

    /**
     * @var array<string,string[]>|null Block XML cut at parse time (blocks-only mode); null = read lazily from $xml
     */
    private $blockXml = null;

    /**
     * @var string[]|null Blocks present in the record (null = not set by the parser)
     */
    private $presentBlocks = null;

    /**
     * @var string|null Imprint GLN (ImprintIDType 06)
     */
    private $imprintGln = null;

    /**
     * @var array<int,array{role:?string,format:?string,date:?string}> Every PublishingDate, as sent
     */
    private $publishingDates = [];

    /**
     * @var ResourceVersion[] Every ResourceVersion of every SupportingResource
     */
    private $resourceVersions = [];

    /**
     * @var Territory[] Market territories of the ProductSupply
     */
    private $marketTerritories = [];

    /**
     * @var array<int,array{type:string,value:string,unit:?string}>|null Measures read by the parser (null = not parsed)
     */
    private $measures = null;

    /**
     * @var array<int,array{type:string,value:string,unit:?string}>|null Extents read by the parser (null = not parsed)
     */
    private $extents = null;
    
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
     * Every <SupplyDetail> of the record, in file order. Nothing is merged: with two distributors
     * you get two objects, each with its own availability and prices.
     *
     * @return SupplyDetail[]
     */
    public function getSupplyDetails(): array
    {
        return $this->supplyDetails;
    }

    public function addSupplyDetail(SupplyDetail $detail): self
    {
        $this->supplyDetails[] = $detail;
        return $this;
    }

    public function getImprintGln(): ?string
    {
        return $this->imprintGln;
    }

    public function setImprintGln(?string $gln): self
    {
        $this->imprintGln = $gln;
        return $this;
    }

    /**
     * Every PublishingDate: role (list 163: 01 publication, 02 embargo, 09 ...), format (list 55)
     * and the date string exactly as sent (dates like 20991231 = undetermined are NOT interpreted here).
     *
     * @return array<int,array{role:?string,format:?string,date:?string}>
     */
    public function getPublishingDates(): array
    {
        return $this->publishingDates;
    }

    public function addPublishingDate(?string $role, ?string $format, ?string $date): self
    {
        $this->publishingDates[] = ['role' => $role, 'format' => $format, 'date' => $date];
        return $this;
    }

    /**
     * Every resource version (each size of each cover or sample), in file order.
     *
     * @param string|null $contentType keep only this ResourceContentType (e.g. '01' front cover)
     * @return ResourceVersion[]
     */
    public function getResourceVersions(?string $contentType = null): array
    {
        if ($contentType === null) {
            return $this->resourceVersions;
        }
        return array_values(array_filter($this->resourceVersions, function (ResourceVersion $v) use ($contentType) {
            return $v->getContentType() === $contentType;
        }));
    }

    public function addResourceVersion(ResourceVersion $version): self
    {
        $this->resourceVersions[] = $version;
        return $this;
    }

    /**
     * The front cover version (ResourceContentType 01) whose longest side is closest to $pixels.
     * Versions with no known size are ignored. On a tie the smaller file wins. Null when none.
     */
    public function getFrontCoverNear(int $pixels = 400): ?ResourceVersion
    {
        $best = null;
        $bestGap = PHP_INT_MAX;
        foreach ($this->getResourceVersions('01') as $v) {
            $side = $v->getLongestSide();
            if ($side === null || !$v->getUrl()) {
                continue;
            }
            $gap = abs($side - $pixels);
            if ($gap < $bestGap || ($gap === $bestGap && $best && ($v->getBytes() ?? PHP_INT_MAX) < ($best->getBytes() ?? PHP_INT_MAX))) {
                $best = $v;
                $bestGap = $gap;
            }
        }
        return $best;
    }

    /**
     * Set the block names found in the record (done by the parser).
     *
     * @param string[] $blocks
     */
    public function setPresentBlocks(array $blocks): self
    {
        $this->presentBlocks = array_values(array_intersect(self::BLOCKS, $blocks));
        return $this;
    }

    /**
     * ONIX 3.0 blocks that this record actually contains, by element name and in message order:
     * DescriptiveDetail, CollateralDetail, ContentDetail, PublishingDetail, RelatedMaterial, ProductSupply.
     *
     * In a DILICOM update (04) a block that is absent was not sent: it is "unchanged", not "deleted".
     *
     * @return string[]
     */
    public function getPresentBlocks(): array
    {
        if ($this->presentBlocks !== null) {
            return $this->presentBlocks;
        }
        // Product built by hand with only XML: derive it
        $found = [];
        if ($this->xml) {
            foreach (self::BLOCKS as $block) {
                if ($this->xml->xpath("*[local-name()='" . $block . "']")) {
                    $found[] = $block;
                }
            }
        }
        return $found;
    }

    public function hasBlock(string $name): bool
    {
        return in_array($name, $this->getPresentBlocks(), true);
    }

    /**
     * Raw XML of one block. Returns EVERY element of that name, in file order, joined with no separator.
     * ProductSupply can appear several times in a record (DILICOM sends one per market: for example the
     * first for FR/BE and a second for WORLD minus FR/BE, the one that covers Morocco), so a single
     * element is not enough. With more than one element the string has several roots: wrap it
     * (`<x>...</x>`) before loading it as a document, or use getBlockElements().
     * Null when the block is absent. Only the six ONIX 3.0 block names are accepted.
     */
    public function getBlockXml(string $name): ?string
    {
        $elements = $this->getBlockElements($name);
        return $elements ? implode('', $elements) : null;
    }

    /**
     * Raw XML of every element of one block, one string per element, in file order.
     * Empty array when the block is absent (or the name is not a block).
     *
     * @return string[]
     */
    public function getBlockElements(string $name): array
    {
        if (!in_array($name, self::BLOCKS, true)) {
            return [];
        }
        if ($this->blockXml !== null) {
            return $this->blockXml[$name] ?? [];
        }
        if (!$this->xml) {
            return [];
        }
        $out = [];
        foreach ($this->xml->xpath("*[local-name()='" . $name . "']") ?: [] as $node) {
            $out[] = $node->asXML();
        }
        return $out;
    }

    /**
     * Used by the parser in blocks-only mode: the block XML is cut out of the record at parse time.
     *
     * @param array<string,string[]> $blocks block name => list of element XML strings
     */
    public function setBlockElements(array $blocks): self
    {
        $this->blockXml = $blocks;
        return $this;
    }

    /**
     * Territories of the <Market> composites of the ProductSupply.
     *
     * @return Territory[]
     */
    public function getMarketTerritories(): array
    {
        return $this->marketTerritories;
    }

    public function addMarketTerritory(Territory $territory): self
    {
        $this->marketTerritories[] = $territory;
        return $this;
    }

    /**
     * The SupplyDetail of one supplier, found by its GLN (SupplierIDType 06), or null.
     */
    public function getSupplyDetailByGln(string $gln): ?SupplyDetail
    {
        foreach ($this->supplyDetails as $detail) {
            if ($detail->getSupplierGln() === $gln) {
                return $detail;
            }
        }
        return null;
    }

    /**
     * Set the measures read by the parser (MeasureType, Measurement, MeasureUnitCode).
     *
     * @param array $measures list of ['type' => '01', 'value' => '215', 'unit' => 'mm']
     * @return self
     */
    public function setMeasures(array $measures)
    {
        $this->measures = $measures;
        return $this;
    }

    /**
     * All measures of the record: list of ['type', 'value', 'unit'].
     *
     * @return array
     */
    public function getMeasures()
    {
        return $this->measures ?? [];
    }

    /**
     * Set the extents read by the parser (ExtentType, ExtentValue, ExtentUnit).
     *
     * @param array $extents list of ['type' => '00', 'value' => '112', 'unit' => '03']
     * @return self
     */
    public function setExtents(array $extents)
    {
        $this->extents = $extents;
        return $this;
    }

    /**
     * All extents of the record: list of ['type', 'value', 'unit'].
     *
     * @return array
     */
    public function getExtents()
    {
        return $this->extents ?? [];
    }

    /**
     * Get page count from product extent information.
     *
     * Pages = ExtentUnit 03 only (DILICOM confirmed 2026-11-05: unit 5 is a duration in minutes).
     * Preference order: type 00 + unit 03, type 03 + unit 03,
     * then the old behaviour (type 00 or 07 with no unit given).
     *
     * @return int|null Number of pages
     */
    public function getPageCount()
    {
        if ($this->extents === null) {
            return $this->getPageCountFromXml();
        }

        $best = null;
        $bestRank = PHP_INT_MAX;
        foreach ($this->extents as $extent) {
            $type = $extent['type'] ?? null;
            $unit = $extent['unit'] ?? null;
            $value = trim((string)($extent['value'] ?? ''));
            if ($value === '' || !ctype_digit($value) || (int)$value <= 0) {
                continue;
            }
            $rank = null;
            if ($unit === '03' && $type === '00') {
                $rank = 1;
            } elseif ($unit === '03' && $type === '03') {
                $rank = 2;
            } elseif (($unit === null || $unit === '') && ($type === '00' || $type === '07')) {
                $rank = 3;
            }
            if ($rank !== null && $rank < $bestRank) {
                $best = (int)$value;
                $bestRank = $rank;
            }
        }
        return $best;
    }

    /**
     * Duration in minutes (ExtentUnit 05), for audio/video products. Never a page count.
     *
     * @return int|null
     */
    public function getDurationMinutes()
    {
        foreach ($this->extents ?? [] as $extent) {
            $value = trim((string)($extent['value'] ?? ''));
            if (($extent['unit'] ?? null) === '05' && ctype_digit($value) && (int)$value > 0) {
                return (int)$value;
            }
        }
        return null;
    }

    /**
     * Old path for Product objects built by hand with only an XML string.
     */
    private function getPageCountFromXml()
    {
        if (!$this->xml) {
            return null;
        }
        $nodes = $this->xml->xpath("//*[local-name()='DescriptiveDetail']/*[local-name()='Extent']");
        $extents = [];
        foreach ($nodes ?: [] as $node) {
            $extents[] = [
                'type' => $this->childText($node, 'ExtentType'),
                'value' => $this->childText($node, 'ExtentValue'),
                'unit' => $this->childText($node, 'ExtentUnit'),
            ];
        }
        $clone = clone $this;
        $clone->extents = $extents;
        $clone->xml = null;
        return $clone->getPageCount();
    }

    /**
     * Text of a direct child, ignoring namespaces.
     */
    private function childText(\SimpleXMLElement $node, string $name): ?string
    {
        $found = $node->xpath("*[local-name()='" . $name . "']");
        if (!$found) {
            return null;
        }
        $value = trim((string)$found[0]);
        return $value === '' ? null : $value;
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
                $code = $this->safeXpath($node, $codePaths);
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
     * Get measurement by type.
     *
     * @param string $measureType ONIX measure type code
     * @return array|null ['value' => float, 'unit' => 'mm', 'unit_name' => 'Millimeters', 'type' => '01']
     */
    private function getMeasurement($measureType)
    {
        $measures = $this->measures;
        if ($measures === null) {
            if (!$this->xml) {
                return null;
            }
            $measures = [];
            $nodes = $this->xml->xpath("//*[local-name()='DescriptiveDetail']/*[local-name()='Measure']");
            foreach ($nodes ?: [] as $node) {
                $measures[] = [
                    'type' => $this->childText($node, 'MeasureType'),
                    'value' => $this->childText($node, 'Measurement'),
                    'unit' => $this->childText($node, 'MeasureUnitCode'),
                ];
            }
        }

        foreach ($measures as $measure) {
            if (($measure['type'] ?? null) !== $measureType) {
                continue;
            }
            $value = trim((string)($measure['value'] ?? ''));
            if ($value === '' || !is_numeric($value)) {
                continue;
            }
            $unitCode = $measure['unit'] ?? null;
            $unitMap = \ONIXParser\CodeMaps::getMeasureUnitMap();
            return [
                'value' => (float)$value,
                'unit' => $unitCode,
                'unit_name' => $unitCode ? ($unitMap[$unitCode] ?? $unitCode) : null,
                'type' => $measureType,
            ];
        }

        return null;
    }

    /**
     * Height in millimetres (converted from the unit the sender used), or null.
     *
     * @return float|null
     */
    public function getHeightMm()
    {
        return self::toMillimetres($this->getHeight());
    }

    /**
     * Width in millimetres, or null.
     *
     * @return float|null
     */
    public function getWidthMm()
    {
        return self::toMillimetres($this->getWidth());
    }

    /**
     * Thickness in millimetres, or null.
     *
     * @return float|null
     */
    public function getThicknessMm()
    {
        return self::toMillimetres($this->getThickness());
    }

    /**
     * Weight in grams (converted from the unit the sender used), or null.
     *
     * @return float|null
     */
    public function getWeightGrams()
    {
        $w = $this->getWeight();
        if (!$w) {
            return null;
        }
        $factor = ['gr' => 1.0, 'g' => 1.0, 'kg' => 1000.0, 'oz' => 28.349523125, 'lb' => 453.59237][$w['unit']] ?? null;
        return $factor === null ? null : round($w['value'] * $factor, 2);
    }

    private static function toMillimetres($m)
    {
        if (!$m) {
            return null;
        }
        $factor = ['mm' => 1.0, 'cm' => 10.0, 'in' => 25.4, 'ft' => 304.8][$m['unit']] ?? null;
        return $factor === null ? null : round($m['value'] * $factor, 2);
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

    /**
     * Get ProductComposition code for DILICOM compliance
     * Uses FieldMappings for XPath queries
     * 
     * ProductComposition indicates the composition/nature of the product:
     * - 00: Single item (default)
     * - 10: Multiple-item retail product 
     * - 11: Multiple-item trade-only product (PROFESSIONAL ACCESS)
     * - 20: Multiple-component retail product
     * - 30: Multiple-component trade-only product (PROFESSIONAL ACCESS)
     * - 31: Trade-only, single-component product (PROFESSIONAL ACCESS)
     * 
     * DILICOM COMPLIANCE: Products with codes 11/20/30/31 require professional access
     * 
     * @return string ProductComposition code or empty string if not found
     */
    public function getProductComposition()
    {
        if (!$this->xml) {
            return '';
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $compositionPaths = $mappings['dilicom_compliance']['product_composition'];
        
        $composition = $this->xml->xpath($compositionPaths[0]) ?: $this->xml->xpath($compositionPaths[1]);
        return $composition && !empty($composition) ? (string)$composition[0] : '';
    }

    /**
     * Get TradeCategory code for DILICOM compliance
     * Uses FieldMappings for XPath queries
     * 
     * TradeCategory indicates the trade classification:
     * - 01: Consumer/retail
     * - 02: Children's books
     * - 03: Young adult books  
     * - 04: Professional/academic
     * - 05: Educational
     * - 06: English language teaching
     * - 07: Distribution
     * - 08: School books/textbooks (DILICOM COMPLIANCE)
     * - 09: Adult education
     * 
     * DILICOM COMPLIANCE: Code 08 identifies school books/textbooks
     * 
     * @return string TradeCategory code or empty string if not found
     */
    public function getTradeCategory()
    {
        if (!$this->xml) {
            return '';
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        
        // Try primary location first
        $categoryPaths = $mappings['dilicom_compliance']['trade_category'];
        $category = $this->xml->xpath($categoryPaths[0]) ?: $this->xml->xpath($categoryPaths[1]);
        
        if (!$category || empty($category)) {
            // Try alternative location
            $altPaths = $mappings['dilicom_compliance']['trade_category_alt'];
            $category = $this->xml->xpath($altPaths[0]) ?: $this->xml->xpath($altPaths[1]);
        }
        
        return $category && !empty($category) ? (string)$category[0] : '';
    }

    /**
     * Get EditionType code for DILICOM compliance
     * Uses FieldMappings for XPath queries
     * 
     * EditionType indicates the type/purpose of the edition:
     * - NOR: Normal edition
     * - REV: Revised edition
     * - ENH: Enhanced edition
     * - ENL: Enlarged edition
     * - EXP: Expanded edition
     * - TCH: Teacher edition (DILICOM COMPLIANCE)
     * - STU: Student edition
     * - FAC: Facsimile edition
     * - REP: Reprint edition
     * 
     * DILICOM COMPLIANCE: TCH indicates teacher-only access required
     * 
     * @return string EditionType code or empty string if not found
     */
    public function getEditionType()
    {
        if (!$this->xml) {
            return '';
        }

        $mappings = \ONIXParser\FieldMappings::getMappings();
        $editionPaths = $mappings['dilicom_compliance']['edition_type'];
        
        $editionType = $this->xml->xpath($editionPaths[0]) ?: $this->xml->xpath($editionPaths[1]);
        return $editionType && !empty($editionType) ? (string)$editionType[0] : '';
    }

    /**
     * Check if product requires professional access
     * Based on ProductComposition codes 11/20/30/31
     * 
     * @return bool True if professional access required
     */
    public function requiresProfessionalAccess()
    {
        $composition = $this->getProductComposition();
        return in_array($composition, ['11', '20', '30', '31']);
    }

    /**
     * Check if product is a school book/textbook
     * Based on TradeCategory code 08
     * 
     * @return bool True if school book/textbook
     */
    public function isSchoolBook()
    {
        return $this->getTradeCategory() === '08';
    }

    /**
     * Check if product is teacher-only
     * Based on EditionType code TCH
     * 
     * @return bool True if teacher-only edition
     */
    public function isTeacherOnly()
    {
        return $this->getEditionType() === 'TCH';
    }

    /**
     * Check if product requires access controls for DILICOM compliance
     * 
     * Product requires access controls if it's:
     * - Professional access (ProductComposition 11/20/30/31)
     * - School book (TradeCategory 08)  
     * - Teacher edition (EditionType TCH)
     * 
     * @return bool True if access controls required
     */
    public function requiresAccessControls()
    {
        return $this->requiresProfessionalAccess() || 
               $this->isSchoolBook() || 
               $this->isTeacherOnly();
    }
    
    /**
     * Safe xpath helper that handles namespace prefix warnings
     * Tries non-namespaced version first for individual elements
     * 
     * @param \SimpleXMLElement $element Element to query
     * @param array $paths Array of xpath expressions [namespaced, non-namespaced]
     * @return \SimpleXMLElement[]|false
     */
    private function safeXpath(\SimpleXMLElement $element, array $paths)
    {
        // For individual elements, try non-namespaced first to avoid warnings
        $result = @$element->xpath($paths[1]);
        if (!$result) {
            $result = @$element->xpath($paths[0]);
        }
        return $result ?: false;
    }
}