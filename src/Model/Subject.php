<?php

namespace ONIXParser\Model;

/**
 * ONIX Subject Model
 * 
 * Represents a subject classification in an ONIX product
 */
class Subject
{
    /**
     * @var string Subject scheme identifier
     * CLIL ('29'), THEMA ('93'-'99'), ScoLOMFR ('A6', 'B3')
     */
    private $scheme;
    
    /**
     * @var string Subject scheme name
     */
    private $schemeName;
    
    /**
     * @var string Subject code value
     */
    private $code;
    
    /**
     * @var string Subject heading text 
     */
    private $headingText;
    
    /**
     * @var string Subject description
     */
    private $description;
    
    /**
     * @var array Parent subject codes
     */
    private $parents = [];
    
    /**
     * @var array Child subject codes
     */
    private $children = [];
    
    /**
     * @var bool Whether this is the main subject
     */
    private $mainSubject = false;
    
    /**
     * @var string Language of the subject label (e.g., 'fr', 'en')
     */
    private $language;
    
    /**
     * Get subject scheme identifier
     * 
     * @return string|null
     */
    public function getScheme()
    {
        return $this->scheme;
    }
    
    /**
     * Set subject scheme identifier
     * 
     * @param string $scheme
     * @return self
     */
    public function setScheme($scheme)
    {
        $this->scheme = $scheme;
        
        // Set scheme name based on code
        switch ($scheme) {
            case '29':
                $this->setSchemeName('CLIL');
                break;
            case '93':
                $this->setSchemeName('THEMA subject category');
                break;
            case '94':
                $this->setSchemeName('THEMA geographical qualifier');
                break;
            case '95':
                $this->setSchemeName('THEMA language qualifier');
                break;
            case '96':
                $this->setSchemeName('THEMA time period qualifier');
                break;
            case '97':
                $this->setSchemeName('THEMA educational purpose qualifier');
                break;
            case '98':
                $this->setSchemeName('THEMA interest age qualifier');
                break;
            case '99':
                $this->setSchemeName('THEMA style qualifier');
                break;
            case 'A6':
                $this->setSchemeName('ScoLOMFR discipline');
                break;
            case 'B3':
                $this->setSchemeName('ScoLOMFR diploma');
                break;
            default:
                $this->setSchemeName('Unknown');
        }
        
        return $this;
    }
    
    /**
     * Get subject scheme name
     * 
     * @return string|null
     */
    public function getSchemeName()
    {
        return $this->schemeName;
    }
    
    /**
     * Set subject scheme name
     * 
     * @param string $schemeName
     * @return self
     */
    public function setSchemeName($schemeName)
    {
        $this->schemeName = $schemeName;
        return $this;
    }
    
    /**
     * Get subject code
     * 
     * @return string|null
     */
    public function getCode()
    {
        return $this->code;
    }
    
    /**
     * Set subject code
     * 
     * @param string $code
     * @return self
     */
    public function setCode($code)
    {
        $this->code = $code;
        return $this;
    }
    
    /**
     * Get subject heading text
     * 
     * @return string|null
     */
    public function getHeadingText()
    {
        return $this->headingText;
    }
    
    /**
     * Set subject heading text
     * 
     * @param string $headingText
     * @return self
     */
    public function setHeadingText($headingText)
    {
        $this->headingText = $headingText;
        return $this;
    }
    
    /**
     * Get subject description
     * 
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }
    
    /**
     * Set subject description
     * 
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }
    
    /**
     * Get parent subject codes
     * 
     * @return array
     */
    public function getParents()
    {
        return $this->parents;
    }
    
    /**
     * Set parent subject codes
     * 
     * @param array $parents
     * @return self
     */
    public function setParents(array $parents)
    {
        $this->parents = $parents;
        return $this;
    }
    
    /**
     * Add a parent subject code
     * 
     * @param string $parent
     * @return self
     */
    public function addParent($parent)
    {
        if (!in_array($parent, $this->parents)) {
            $this->parents[] = $parent;
        }
        return $this;
    }
    
    /**
     * Get child subject codes
     * 
     * @return array
     */
    public function getChildren()
    {
        return $this->children;
    }
    
    /**
     * Set child subject codes
     * 
     * @param array $children
     * @return self
     */
    public function setChildren(array $children)
    {
        $this->children = $children;
        return $this;
    }
    
    /**
     * Add a child subject code
     * 
     * @param string $child
     * @return self
     */
    public function addChild($child)
    {
        if (!in_array($child, $this->children)) {
            $this->children[] = $child;
        }
        return $this;
    }
    
    /**
     * Check if this is the main subject
     * 
     * @return bool
     */
    public function isMainSubject()
    {
        return $this->mainSubject;
    }
    
    /**
     * Set whether this is the main subject
     * 
     * @param bool $mainSubject
     * @return self
     */
    public function setMainSubject($mainSubject)
    {
        $this->mainSubject = (bool)$mainSubject;
        return $this;
    }
    
    /**
     * Get language of the subject label
     * 
     * @return string|null
     */
    public function getLanguage()
    {
        return $this->language;
    }
    
    /**
     * Set language of the subject label
     * 
     * @param string $language
     * @return self
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }
    
    /**
     * Check if this is a CLIL subject
     * 
     * @return bool
     */
    public function isClil()
    {
        return $this->scheme === '29';
    }
    
    /**
     * Check if this is a THEMA subject
     * 
     * @return bool
     */
    public function isThema()
    {
        return in_array($this->scheme, ['93', '94', '95', '96', '97', '98', '99']);
    }
    
    /**
     * Check if this is a ScoLOMFR subject
     * 
     * @return bool
     */
    public function isScoLOMFR()
    {
        return in_array($this->scheme, ['A6', 'B3']);
    }
    
    /**
     * Check if subject has children
     * 
     * @return bool
     */
    public function hasChildren()
    {
        return !empty($this->children);
    }
    
    /**
     * Check if subject has parents
     * 
     * @return bool
     */
    public function hasParents()
    {
        return !empty($this->parents);
    }
    
    /**
     * Get a string representation of the subject
     * 
     * @return string
     */
    public function __toString()
    {
        if ($this->headingText) {
            return $this->headingText;
        }
        
        if ($this->code) {
            return $this->getSchemeName() . ': ' . $this->code;
        }
        
        return 'Unknown subject';
    }
}