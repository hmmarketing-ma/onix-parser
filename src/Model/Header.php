<?php

namespace ONIXParser\Model;

/**
 * ONIX Header Model
 * 
 * Represents the Header section of an ONIX message
 */
class Header
{
    /**
     * @var string Sender name
     */
    private $sender;
    
    /**
     * @var string Contact name
     */
    private $contact;
    
    /**
     * @var string Contact email address
     */
    private $email;
    
    /**
     * @var string Date and time when the message was sent
     */
    private $sentDateTime;
    
    /**
     * @var \SimpleXMLElement Original XML
     */
    private $xml;

    /**
     * Get sender name
     * 
     * @return string|null
     */
    public function getSender()
    {
        return $this->sender;
    }

    /**
     * Set sender name
     * 
     * @param string $sender
     * @return self
     */
    public function setSender($sender)
    {
        $this->sender = $sender;
        return $this;
    }

    /**
     * Get contact name
     * 
     * @return string|null
     */
    public function getContact()
    {
        return $this->contact;
    }

    /**
     * Set contact name
     * 
     * @param string $contact
     * @return self
     */
    public function setContact($contact)
    {
        $this->contact = $contact;
        return $this;
    }

    /**
     * Get contact email address
     * 
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set contact email address
     * 
     * @param string $email
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * Get sent date and time
     * 
     * @return string|null
     */
    public function getSentDateTime()
    {
        return $this->sentDateTime;
    }

    /**
     * Set sent date and time
     * 
     * @param string $sentDateTime
     * @return self
     */
    public function setSentDateTime($sentDateTime)
    {
        $this->sentDateTime = $sentDateTime;
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
}