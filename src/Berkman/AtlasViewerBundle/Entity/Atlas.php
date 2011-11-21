<?php

namespace Berkman\AtlasViewerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Berkman\AtlasViewerBundle\Entity\Atlas
 */
class Atlas
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
     * @var text $description
     */
    private $description;

    /**
     * @var string $url
     */
    private $url;

    /**
     * @var integer $default_epsg_code
     */
    private $default_epsg_code;

    /**
     * @var datetime $created
     */
    private $created;

    /**
     * @var datetime $updated
     */
    private $updated;

    /**
     * @var Berkman\AtlasViewerBundle\Entity\Page
     */
    private $pages;

    /**
     * @var Berkman\AtlasViewerBundle\Entity\Person
     */
    private $owner;

    /**
     * @var array $bounds
     */
    private $bounds;

    /**
     * @var integer $min_zoom_level
     */
    private $min_zoom_level;

    /**
     * @var integer $max_zoom_level
     */
    private $max_zoom_level;


    public function __construct($importUrl = null, $workingDir = null, $outputDir = null)
    {
        $this->pages = new \Doctrine\Common\Collections\ArrayCollection();
        if ($importUrl && $workingDir && $outputDir) {
            $this->import($importUrl, $workingDir, $outputDir);
        }
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
     * Set description
     *
     * @param text $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * Get description
     *
     * @return text 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set url
     *
     * @param string $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * Get url
     *
     * @return string 
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set default_epsg_code
     *
     * @param integer $defaultEpsgCode
     */
    public function setDefaultEpsgCode($defaultEpsgCode)
    {
        $this->default_epsg_code = $defaultEpsgCode;
    }

    /**
     * Get default_epsg_code
     *
     * @return integer 
     */
    public function getDefaultEpsgCode()
    {
        return $this->default_epsg_code;
    }

    /**
     * Set created
     *
     * @param datetime $created
     */
    public function setCreated($created)
    {
        $this->created = $created;
    }

    /**
     * Get created
     *
     * @return datetime 
     */
    public function getCreated()
    {
        return $this->created;
    }

    /**
     * Set updated
     *
     * @param datetime $updated
     */
    public function setUpdated($updated)
    {
        $this->updated = $updated;
    }

    /**
     * Get updated
     *
     * @return datetime 
     */
    public function getUpdated()
    {
        return $this->updated;
    }

    /**
     * Add pages
     *
     * @param Berkman\AtlasViewerBundle\Entity\Page $pages
     */
    public function addPage(\Berkman\AtlasViewerBundle\Entity\Page $pages)
    {
        $this->pages[] = $pages;
    }

    /**
     * Get pages
     *
     * @return Doctrine\Common\Collections\Collection 
     */
    public function getPages()
    {
        return $this->pages;
    }

    /**
     * Set Pages
     *
     */
    public function setPages(ArrayCollection $pages)
    {
        $this->pages = $pages;
    }

    /**
     * Set owner
     *
     * @param Berkman\AtlasViewerBundle\Entity\Person $owner
     */
    public function setOwner(\Berkman\AtlasViewerBundle\Entity\Person $owner)
    {
        $this->owner = $owner;
    }

    /**
     * Get owner
     *
     * @return Berkman\AtlasViewerBundle\Entity\Person 
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Set bounds
     *
     * @param array $bounds
     */
    public function setBounds($bounds)
    {
        $this->bounds = $bounds;
    }

    /**
     * Get bounds
     *
     * @return array 
     */
    public function getBounds()
    {
        return $this->bounds;
    }

    /**
     * Set min_zoom_level
     *
     * @param integer $minZoomLevel
     */
    public function setMinZoomLevel($minZoomLevel)
    {
        $this->min_zoom_level = $minZoomLevel;
    }

    /**
     * Get min_zoom_level
     *
     * @return integer 
     */
    public function getMinZoomLevel()
    {
        return $this->min_zoom_level;
    }

    /**
     * Set max_zoom_level
     *
     * @param integer $maxZoomLevel
     */
    public function setMaxZoomLevel($maxZoomLevel)
    {
        $this->max_zoom_level = $maxZoomLevel;
    }

    /**
     * Get max_zoom_level
     *
     * @return integer 
     */
    public function getMaxZoomLevel()
    {
        return $this->max_zoom_level;
    }
}
