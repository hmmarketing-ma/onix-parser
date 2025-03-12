<?php

namespace ONIXParser\Model;

/**
 * ONIX Description Model
 * 
 * Represents a text content description in an ONIX product
 */
class Description
{
    /**
     * @var string Text type code
     */
    private $type;
    
    /**
     * @var string Type name (human-readable)
     */
    private $typeName;
    
    /**
     * @var string Text format code (00=ASCII, 02=HTML, 03=XML, etc.)
     */
    private $format;
    
    /**
     * @var string Format name (human-readable)
     */
    private $formatName;
    
    /**
     * @var string Text content
     */
    private $content;
    
    /**
     * Get text type code
     * 
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }
    
    /**
     * Set text type code
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
     * Get type name
     * 
     * @return string|null
     */
    public function getTypeName()
    {
        return $this->typeName;
    }
    
    /**
     * Set type name
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
     * Get text format code
     * 
     * @return string|null
     */
    public function getFormat()
    {
        return $this->format;
    }
    
    /**
     * Set text format code
     * 
     * @param string $format
     * @return self
     */
    public function setFormat($format)
    {
        $this->format = $format;
        return $this;
    }
    
    /**
     * Get format name
     * 
     * @return string|null
     */
    public function getFormatName()
    {
        return $this->formatName;
    }
    
    /**
     * Set format name
     * 
     * @param string $formatName
     * @return self
     */
    public function setFormatName($formatName)
    {
        $this->formatName = $formatName;
        return $this;
    }
    
    /**
     * Get text content
     * 
     * @return string|null
     */
    public function getContent()
    {
        return $this->content;
    }
    
    /**
     * Set text content
     * 
     * @param string $content
     * @return self
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }
    
    /**
     * Check if this is a main description
     * 
     * @return bool
     */
    public function isMainDescription()
    {
        return $this->type === '01';
    }
    
    /**
     * Check if this is a short description
     * 
     * @return bool
     */
    public function isShortDescription()
    {
        return $this->type === '02';
    }
    
    /**
     * Check if this is a long description
     * 
     * @return bool
     */
    public function isLongDescription()
    {
        return $this->type === '03';
    }
    
    /**
     * Check if this is a table of contents
     * 
     * @return bool
     */
    public function isTableOfContents()
    {
        return $this->type === '04';
    }
    
    /**
     * Check if this is a review quote
     * 
     * @return bool
     */
    public function isReviewQuote()
    {
        return $this->type === '05';
    }
    
    /**
     * Check if this is HTML formatted
     * 
     * @return bool
     */
    public function isHtml()
    {
        return $this->format === '02';
    }
    
    /**
     * Get plain text version of the content (with HTML stripped if necessary)
     * 
     * @return string|null
     */
    public function getPlainText()
    {
        if (empty($this->content)) {
            return null;
        }
        
        if ($this->isHtml()) {
            return strip_tags($this->content);
        }
        
        return $this->content;
    }
    
    /**
     * Get a short excerpt of the content
     * 
     * @param int $length Maximum length
     * @param string $suffix Suffix to add if content is truncated
     * @return string|null
     */
    public function getExcerpt($length = 200, $suffix = '...')
    {
        $text = $this->getPlainText();
        
        if (empty($text)) {
            return null;
        }
        
        if (mb_strlen($text) <= $length) {
            return $text;
        }
        
        return mb_substr($text, 0, $length) . $suffix;
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->getPlainText();
    }
}