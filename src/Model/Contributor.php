<?php

namespace ONIXParser\Model;

/**
 * ONIX Contributor Model
 * 
 * Represents a contributor (author, editor, etc.) in an ONIX product
 */
class Contributor
{
    /**
     * @var string Contributor role code (e.g., 'A01' for Author)
     */
    private $role;
    
    /**
     * @var string Contributor role name
     */
    private $roleName;
    
    /**
     * @var string Contributor name
     */
    private $name;
    
    /**
     * @var string Person name prefix (e.g., "Mr", "Dr")
     */
    private $namePrefix;
    
    /**
     * @var string Person name given/first name
     */
    private $nameGiven;
    
    /**
     * @var string Person name family/last name
     */
    private $nameFamily;
    
    /**
     * @var string Person name suffix (e.g., "Jr", "III")
     */
    private $nameSuffix;
    
    /**
     * @var string Key names (for alphabetical sorting)
     */
    private $keyNames;
    
    /**
     * @var string Corporate name (for organizations)
     */
    private $corporateName;
    
    /**
     * @var string Biographical note
     */
    private $biographicalNote;

    /**
     * Get contributor role code
     * 
     * @return string|null
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Set contributor role code
     * 
     * @param string $role
     * @return self
     */
    public function setRole($role)
    {
        $this->role = $role;
        return $this;
    }

    /**
     * Get contributor role name
     * 
     * @return string|null
     */
    public function getRoleName()
    {
        return $this->roleName;
    }

    /**
     * Set contributor role name
     * 
     * @param string $roleName
     * @return self
     */
    public function setRoleName($roleName)
    {
        $this->roleName = $roleName;
        return $this;
    }

    /**
     * Get contributor name (formatted display name)
     * 
     * @return string|null
     */
    public function getName()
    {
        if ($this->name) {
            return $this->name;
        }
        
        if ($this->corporateName) {
            return $this->corporateName;
        }
        
        // Build name from parts
        $parts = array_filter([
            $this->namePrefix,
            $this->nameGiven,
            $this->nameFamily,
            $this->nameSuffix
        ]);
        
        return !empty($parts) ? implode(' ', $parts) : null;
    }

    /**
     * Set contributor name
     * 
     * @param string $name
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name prefix
     * 
     * @return string|null
     */
    public function getNamePrefix()
    {
        return $this->namePrefix;
    }

    /**
     * Set name prefix
     * 
     * @param string $namePrefix
     * @return self
     */
    public function setNamePrefix($namePrefix)
    {
        $this->namePrefix = $namePrefix;
        return $this;
    }

    /**
     * Get given name
     * 
     * @return string|null
     */
    public function getNameGiven()
    {
        return $this->nameGiven;
    }

    /**
     * Set given name
     * 
     * @param string $nameGiven
     * @return self
     */
    public function setNameGiven($nameGiven)
    {
        $this->nameGiven = $nameGiven;
        return $this;
    }

    /**
     * Get family name
     * 
     * @return string|null
     */
    public function getNameFamily()
    {
        return $this->nameFamily;
    }

    /**
     * Set family name
     * 
     * @param string $nameFamily
     * @return self
     */
    public function setNameFamily($nameFamily)
    {
        $this->nameFamily = $nameFamily;
        return $this;
    }

    /**
     * Get name suffix
     * 
     * @return string|null
     */
    public function getNameSuffix()
    {
        return $this->nameSuffix;
    }

    /**
     * Set name suffix
     * 
     * @param string $nameSuffix
     * @return self
     */
    public function setNameSuffix($nameSuffix)
    {
        $this->nameSuffix = $nameSuffix;
        return $this;
    }

    /**
     * Get key names
     * 
     * @return string|null
     */
    public function getKeyNames()
    {
        return $this->keyNames;
    }

    /**
     * Set key names
     * 
     * @param string $keyNames
     * @return self
     */
    public function setKeyNames($keyNames)
    {
        $this->keyNames = $keyNames;
        return $this;
    }

    /**
     * Get corporate name
     * 
     * @return string|null
     */
    public function getCorporateName()
    {
        return $this->corporateName;
    }

    /**
     * Set corporate name
     * 
     * @param string $corporateName
     * @return self
     */
    public function setCorporateName($corporateName)
    {
        $this->corporateName = $corporateName;
        return $this;
    }

    /**
     * Get biographical note
     * 
     * @return string|null
     */
    public function getBiographicalNote()
    {
        return $this->biographicalNote;
    }

    /**
     * Set biographical note
     * 
     * @param string $biographicalNote
     * @return self
     */
    public function setBiographicalNote($biographicalNote)
    {
        $this->biographicalNote = $biographicalNote;
        return $this;
    }
}