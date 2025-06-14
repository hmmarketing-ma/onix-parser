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
}