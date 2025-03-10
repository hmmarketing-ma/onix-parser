<?php

namespace ONIXParser\Model;

/**
 * ONIX Message Model
 * 
 * Represents an ONIX message containing header information and products
 */
class Onix
{
    /**
     * @var array<Product> All products in the ONIX message
     */
    private $products = array();

    /**
     * @var array<Product> Only available products
     */
    private $productsAvailable = array();

    /**
     * @var Header Header information
     */
    private $header;

    /**
     * @var string ONIX version
     */
    private $version;

    /**
     * Add a product to the ONIX message
     * 
     * @param Product $product Product to add
     */
    public function setProduct(Product $product)
    {
        $this->products[] = $product;
        
        if ($product->isAvailable()) {
            $this->productsAvailable[] = $product;
        }
    }

    /**
     * Get all products in the ONIX message
     * 
     * @return array<Product>
     */
    public function getProducts()
    {
        return $this->products;
    }

    /**
     * Get only available products
     * 
     * @return array<Product>
     */
    public function getProductsAvailable()
    {
        return $this->productsAvailable;
    }

    /**
     * Set header information
     * 
     * @param Header $header
     */
    public function setHeader(Header $header)
    {
        $this->header = $header;
    }

    /**
     * Get header information
     * 
     * @return Header|null
     */
    public function getHeader()
    {
        return $this->header;
    }

    /**
     * Set ONIX version
     * 
     * @param string $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * Get ONIX version
     * 
     * @return string|null
     */
    public function getVersion()
    {
        return $this->version;
    }
    
    /**
     * Find a product by ISBN
     * 
     * @param string $isbn
     * @return Product|null
     */
    public function findProductByIsbn(string $isbn)
    {
        foreach ($this->products as $product) {
            if ($product->getIsbn() === $isbn) {
                return $product;
            }
        }
        
        return null;
    }
    
    /**
     * Find a product by EAN
     * 
     * @param string $ean
     * @return Product|null
     */
    public function findProductByEan(string $ean)
    {
        foreach ($this->products as $product) {
            if ($product->getEan() === $ean) {
                return $product;
            }
        }
        
        return null;
    }
    
    /**
     * Get product count
     * 
     * @return int
     */
    public function getProductCount()
    {
        return count($this->products);
    }
    
    /**
     * Get available product count
     * 
     * @return int
     */
    public function getAvailableProductCount()
    {
        return count($this->productsAvailable);
    }
}