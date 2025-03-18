<?php

namespace ONIXParser\Model;

/**
 * ONIX Collection Model
 * 
 * Represents a collection or series in an ONIX product
 */
class Collection
{
    /**
     * @var string Collection type code
     */
    private $type;
    
    /**
     * @var string Collection type name (human-readable)
     */
    private $typeName;
    
    /**
     * @var string Collection title text
     */
    private $titleText;
    
    /**
     * @var string Collection subtitle (if available)
     */
    private $subtitle;
    
    /**
     * @var string Part number within collection
     */
    private $partNumber;
    
    /**
     * @var array Additional titles keyed by title type
     */
    private $additionalTitles = [];
    
    /**
     * Get collection type code
     * 
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Set collection type code
     * 
     * @param string $type
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }
    
    /**
     * Get collection type name
     * 
     * @return string|null
     */
    public function getTypeName()
    {
        return $this->typeName;
    }
    
    /**
     * Set collection type name
     * 
     * @param string $typeName
     * @return self
     */
    public function setTypeName($typeName)
    {
        $this->typeName = $typeName;
        return $this;
    }
    
    /**
     * Get collection title text
     * 
     * @return string|null
     */
    public function getTitleText()
    {
        return $this->titleText;
    }
    
    /**
     * Set collection title text
     * 
     * @param string $titleText
     * @return self
     */
    public function setTitleText($titleText)
    {
        $this->titleText = $titleText;
        return $this;
    }
    
    /**
     * Get subtitle
     * 
     * @return string|null
     */
    public function getSubtitle()
    {
        return $this->subtitle;
    }
    
    /**
     * Set subtitle
     * 
     * @param string $subtitle
     * @return self
     */
    public function setSubtitle($subtitle)
    {
        $this->subtitle = $subtitle;
        return $this;
    }
    
    /**
     * Get part number within collection
     * 
     * @return string|null
     */
    public function getPartNumber()
    {
        return $this->partNumber;
    }
    
    /**
     * Set part number within collection
     * 
     * @param string $partNumber
     * @return self
     */
    public function setPartNumber($partNumber)
    {
        $this->partNumber = $partNumber;
        return $this;
    }
    
    /**
     * Add an additional title for this collection
     * 
     * @param string $titleType The title type code
     * @param string $level The title element level code
     * @param string $text The title text
     * @return self
     */
    public function addAdditionalTitle($titleType, $level, $text)
    {
        if (!isset($this->additionalTitles[$titleType])) {
            $this->additionalTitles[$titleType] = [];
        }
        
        $this->additionalTitles[$titleType][$level] = $text;
        return $this;
    }
    
    /**
     * Get all additional titles
     * 
     * @return array
     */
    public function getAdditionalTitles()
    {
        return $this->additionalTitles;
    }
    
    /**
     * Get titles of a specific type
     * 
     * @param string $titleType Title type code
     * @return array|null
     */
    public function getTitlesByType($titleType)
    {
        return $this->additionalTitles[$titleType] ?? null;
    }
    
    /**
     * Check if this is a series
     * 
     * @return bool
     */
    public function isSeries()
    {
        return $this->type === '10';
    }
    
    /**
     * Check if this is a collection
     * 
     * @return bool
     */
    public function isCollection()
    {
        return $this->type === '11';
    }
    
    /**
     * Get full title with subtitle if available
     * 
     * @return string|null
     */
    public function getFullTitle()
    {
        if (empty($this->titleText)) {
            return null;
        }
        
        if (!empty($this->subtitle)) {
            return $this->titleText . ': ' . $this->subtitle;
        }
        
        return $this->titleText;
    }
    
    /**
     * Get display name with part number if available
     * 
     * @return string|null
     */
    public function getDisplayName()
    {
        if (empty($this->titleText)) {
            return null;
        }
        
        if (!empty($this->partNumber)) {
            return $this->titleText . ' #' . $this->partNumber;
        }
        
        return $this->titleText;
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getDisplayName();
    }
}