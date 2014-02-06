<?php

namespace Heri\Bundle\WebServiceBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Heri\Bundle\WebServiceBundle\Entity\Sample
 *
 * @ORM\Table(name="sample")
 * @ORM\Entity
 */
class Sample
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;
    
    /**
     * @ORM\Column(type="string", length=80, nullable=false)
     */
    protected $label;
    
    /**
     * @ORM\Column(name="publication_status", type="string", length=80, unique=true, nullable=true)
     */
    protected $publicationStatus;
	
    /**
     * @ORM\Column(name="to_update", type="boolean")
     */
    protected $toUpdate;
	
    /**
     * Adds synchronization in specified queue
     * 
     * @return string
     */
    //public function synchronize()
    //{
    //    return 'php:unit';
    //}

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set label
     *
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set toUpdate
     *
     * @param boolean $toUpdate
     */
    public function setToUpdate($toUpdate)
    {
        $this->toUpdate = $toUpdate;
    }

    /**
     * Get toUpdate
     *
     * @return boolean 
     */
    public function getToUpdate()
    {
        return $this->toUpdate;
    }

    /**
     * Set publicationStatus
     *
     * @param string $publicationStatus
     */
    public function setPublicationStatus($publicationStatus)
    {
        $this->publicationStatus = $publicationStatus;
    }

    /**
     * Get publicationStatus
     *
     * @return string 
     */
    public function getPublicationStatus()
    {
        return $this->publicationStatus;
    }
}