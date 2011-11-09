<?php

namespace Berkman\AtlasViewerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Berkman\AtlasViewerBundle\Entity\Page
 */
class Page
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var string $name
     */
    private $name;

    /**
     * @var integer $epsg_code
     */
    private $epsg_code;

    /**
     * @var text $metadata
     */
    private $metadata;

    /**
     * @var Berkman\AtlasViewerBundle\Entity\Atlas
     */
    private $atlas;


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
     * Set name
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Get name
     *
     * @return string 
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set epsg_code
     *
     * @param integer $epsgCode
     */
    public function setEpsgCode($epsgCode)
    {
        $this->epsg_code = $epsgCode;
    }

    /**
     * Get epsg_code
     *
     * @return integer 
     */
    public function getEpsgCode()
    {
        return $this->epsg_code;
    }

    /**
     * Set metadata
     *
     * @param text $metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get metadata
     *
     * @return text 
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Set atlas
     *
     * @param Berkman\AtlasViewerBundle\Entity\Atlas $atlas
     */
    public function setAtlas(\Berkman\AtlasViewerBundle\Entity\Atlas $atlas)
    {
        $this->atlas = $atlas;
    }

    /**
     * Get atlas
     *
     * @return Berkman\AtlasViewerBundle\Entity\Atlas 
     */
    public function getAtlas()
    {
        return $this->atlas;
    }
}