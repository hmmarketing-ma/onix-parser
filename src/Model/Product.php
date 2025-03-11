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
     * @var array<Subject> Subjects associated with the product
     */
    private $subjects = [];

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
}