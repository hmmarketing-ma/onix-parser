<?php

namespace ONIXParser\Model;

use ONIXParser\CodeMaps;

/**
 * ONIX Price Model
 * 
 * Represents a price in an ONIX product
 */
class Price
{
    /**
     * @var string Price type code
     */
    private $type;
    
    /**
     * @var float Price amount
     */
    private $amount;
    
    /**
     * @var string Currency code (ISO 4217)
     */
    private $currency;
    
    /**
     * @var float Tax rate percentage
     */
    private $taxRate;

    /**
     * @var string Tax type code
     */
    private $taxType;

    /**
     * @var string Tax rate code
     */
    private $taxRateCode;
    
    /**
     * @var bool Whether the product is free
     */
    private $free = false;
    
    /**
     * @var string Unpriced item type
     */
    private $unpricedItemType;

    /**
     * Get price type code
     * 
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set price type code
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
     * Get price amount
     * 
     * @return float|null
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * Set price amount
     * 
     * @param float $amount
     * @return self
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
        return $this;
    }

    /**
     * Get currency code
     * 
     * @return string|null
     */
    public function getCurrency()
    {
        return $this->currency;
    }

    /**
     * Set currency code
     * 
     * @param string $currency
     * @return self
     */
    public function setCurrency($currency)
    {
        $this->currency = $currency;
        return $this;
    }

    /**
     * Get tax rate percentage
     * 
     * @return float|null
     */
    public function getTaxRate()
    {
        return $this->taxRate;
    }

    /**
     * Set tax rate percentage
     * 
     * @param float $taxRate
     * @return self
     */
    public function setTaxRate($taxRate)
    {
        $this->taxRate = $taxRate;
        return $this;
    }

    /**
     * Check if product is free
     * 
     * @return bool
     */
    public function isFree()
    {
        return $this->free;
    }

    /**
     * Set whether the product is free
     * 
     * @param bool $free
     * @return self
     */
    public function setFree($free)
    {
        $this->free = (bool)$free;
        return $this;
    }

    /**
     * Get unpriced item type
     * 
     * @return string|null
     */
    public function getUnpricedItemType()
    {
        return $this->unpricedItemType;
    }

    /**
     * Set unpriced item type
     * 
     * @param string $unpricedItemType
     * @return self
     */
    public function setUnpricedItemType($unpricedItemType)
    {
        $this->unpricedItemType = $unpricedItemType;
        return $this;
    }
    
    /**
     * Get formatted price with currency
     * 
     * @return string
     */
    public function getFormattedPrice()
    {
        if ($this->isFree()) {
            return 'Free';
        }
        
        if (!$this->amount) {
            return 'N/A';
        }
        
        return number_format($this->amount, 2) . ' ' . ($this->currency ?? '');
    }
    
    /**
     * Get price with tax included
     * 
     * @return float|null
     */
    public function getAmountWithTax()
    {
        if (!$this->amount) {
            return null;
        }
        
        if (!$this->taxRate) {
            return $this->amount;
        }
        
        return $this->amount * (1 + ($this->taxRate / 100));
    }
    
    /**
     * Convert to string
     * 
     * @return string
     */
    public function __toString()
    {
        return $this->getFormattedPrice();
    }


    /**
     * Get tax type code
     * 
     * @return string|null
     */
    public function getTaxType()
    {
        return $this->taxType;
    }

    /**
     * Set tax type code
     * 
     * @param string $taxType
     * @return self
     */
    public function setTaxType($taxType)
    {
        $this->taxType = $taxType;
        return $this;
    }

    /**
     * Get tax rate code
     * 
     * @return string|null
     */
    public function getTaxRateCode()
    {
        return $this->taxRateCode;
    }

    /**
     * Set tax rate code
     * 
     * @param string $taxRateCode
     * @return self
     */
    public function setTaxRateCode($taxRateCode)
    {
        $this->taxRateCode = $taxRateCode;
        return $this;
    }
    
    /**
     * Get price type name
     * 
     * @return string|null
     */
    public function getPriceTypeName()
    {
        if (!$this->type) {
            return null;
        }
        
        $priceTypeMap = CodeMaps::getPriceTypeMap();
        return isset($priceTypeMap[$this->type]) ? $priceTypeMap[$this->type] : null;
    }
}