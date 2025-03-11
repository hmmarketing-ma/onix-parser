<?php

namespace ONIXParser\Model;

/**
 * ONIX Image Model
 * 
 * Represents an image or other supporting resource in an ONIX product
 */
class Image
{
    /**
     * @var string Resource content type code
     */
    private $contentType;
    
    /**
     * @var string Content type name (human-readable)
     */
    private $contentTypeName;
    
    /**
     * @var string Resource mode code
     */
    private $mode;
    
    /**
     * @var string Mode name (human-readable)
     */
    private $modeName;
    
    /**
     * @var string Resource URL
     */
    private $url;
    
    /**
     * Get resource content type code
     * 
     * @return string|null
     */
    public function getContentType()
    {
        return $this->contentType;
    }
    
    /**
     * Set resource content type code
     * 
     * @param string $contentType
     * @return self
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
        return $this;
    }
    
    /**
     * Get content type name
     * 
     * @return string|null
     */
    public function getContentTypeName()
    {
        return $this->contentTypeName;
    }
    
    /**
     * Set content type name
     * 
     * @param string $contentTypeName
     * @return self
     */
    public function setContentTypeName($contentTypeName)
    {
        $this->contentTypeName = $contentTypeName;
        return $this;
    }
    
    /**
     * Get resource mode code
     * 
     * @return string|null
     */
    public function getMode()
    {
        return $this->mode;
    }
    
    /**
     * Set resource mode code
     * 
     * @param string $mode
     * @return self
     */
    public function setMode($mode)
    {
        $this->mode = $mode;
        return $this;
    }
    
    /**
     * Get mode name
     * 
     * @return string|null
     */
    public function getModeName()
    {
        return $this->modeName;
    }
    
    /**
     * Set mode name
     * 
     * @param string $modeName
     * @return self
     */
    public function setModeName($modeName)
    {
        $this->modeName = $modeName;
        return $this;
    }
    
    /**
     * Get resource URL
     * 
     * @return string|null
     */
    public function getUrl()
    {
        return $this->url;
    }
    
    /**
     * Set resource URL
     * 
     * @param string $url
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Check if this is a cover image
     * 
     * @return bool
     */
    public function isCoverImage()
    {
        return $this->contentType === '01';
    }
    
    /**
     * Check if this is a sample content
     * 
     * @return bool
     */
    public function isSampleContent()
    {
        return $this->contentType === '15';
    }
    
    /**
     * Check if this is an image
     * 
     * @return bool
     */
    public function isImage()
    {
        return $this->mode === '03';
    }
    
    /**
     * Check if this is a video
     * 
     * @return bool
     */
    public function isVideo()
    {
        return $this->mode === '04';
    }
    
    /**
     * Check if this is an audio resource
     * 
     * @return bool
     */
    public function isAudio()
    {
        return $this->mode === '02';
    }
    
    /**
     * Get HTML img tag for this image
     * 
     * @param array $attributes Additional attributes for img tag
     * @return string|null
     */
    public function getImageTag($attributes = [])
    {
        if (!$this->isImage() || empty($this->url)) {
            return null;
        }
        
        $attrs = '';
        foreach ($attributes as $key => $value) {
            $attrs .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
        }
        
        return '<img src="' . htmlspecialchars($this->url) . '"' . $attrs . '>';
    }
    
    /**
     * Get file extension from URL
     * 
     * @return string|null
     */
    public function getFileExtension()
    {
        if (empty($this->url)) {
            return null;
        }
        
        // Remove query string before getting extension
        $urlPath = parse_url($this->url, PHP_URL_PATH);
        if (!$urlPath) {
            return null;
        }
        
        $pathInfo = pathinfo($urlPath);
        return isset($pathInfo['extension']) ? strtolower($pathInfo['extension']) : null;
    }
    
    /**
     * Check if URL is valid
     * 
     * @return bool
     */
    public function hasValidUrl()
    {
        return !empty($this->url) && filter_var($this->url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function __toString()
    {
        return (string) $this->url;
    }
}