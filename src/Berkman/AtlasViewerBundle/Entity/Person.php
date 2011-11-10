<?php

namespace Berkman\AtlasViewerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use FOS\UserBundle\Entity\User as BaseUser;

/**
 * Berkman\AtlasViewerBundle\Entity\Person
 */
class Person extends BaseUser
{
    /**
     * @var integer $id
     */
    protected $id;

    /**
     * @var Berkman\AtlasViewerBundle\Entity\Atlas
     */
    private $atlases;

    public function __construct()
    {
        parent::__construct();
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
