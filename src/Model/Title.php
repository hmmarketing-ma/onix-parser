<?php

namespace ONIXParser\Model;

/**
 * ONIX Title Model
 * 
 * Represents a title in an ONIX product
 */
class Title
{
    /**
     * @var string Main title text
     */
    private $text;
    
    /**
     * @var string Subtitle
     */
    private $subtitle;
    
    /**
     * Get main title text
     * 
     * @return string|null
     */
    public function getText()
    {
        return $this->text;
    }
    
    /**
     * Set main title text
     * 
     * @param string $text
     * @return self
     */
    public function setText($text)
    {
        $this->text = $text;
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
     * Get full title (title and subtitle)
     * 
     * @param string $separator Separator between title and subtitle
     * @return string
     */
    public function getFullTitle($separator = ': ')
    {
        if (!$this->text) {
            return '';
        }
        
        if ($this->subtitle) {
            return $this->text . $separator . $this->subtitle;
        }
        
        return $this->text;
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getFullTitle();
    }
}