<?php

namespace Berkman\AtlasViewerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Berkman\AtlasViewerBundle\Entity\Person
 */
class Person
{
    /**
     * @var integer $id
     */
    private $id;

    /**
     * @var Berkman\AtlasViewerBundle\Entity\Atlas
     */
    private $atlases;

    public function __construct()
    {
        $this->atlases = new \Doctrine\Common\Collections\ArrayCollection();
    }
    
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
     * Add atlases
     *
     * @param Berkman\AtlasViewerBundle\Entity\Atlas $atlases
     */
    public function addAtlas(\Berkman\AtlasViewerBundle\Entity\Atlas $atlases)
    {
        $this->atlases[] = $atlases;
    }

    /**
     * Get atlases
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getAtlases()
    {
        return $this->atlases;
    }
}