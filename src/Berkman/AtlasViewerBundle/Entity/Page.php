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
     * @var array $metadata
     */
    private $metadata;

    /**
     * @var Berkman\AtlasViewerBundle\Entity\Atlas
     */
    private $atlas;

    /**
     * @var array $bounding_box
     */
    private $bounding_box;

    /**
     * @var integer $min_zoom_level
     */
    private $min_zoom_level;

    /**
     * @var integer $max_zoom_level
     */
    private $max_zoom_level;
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
     * @param array $metadata
     */
    public function setMetadata($metadata)
    {
        $this->metadata = $metadata;
    }

    /**
     * Get metadata
     *
     * @return array 
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


    /**
     * Set bounding_box
     *
     * @param array $boundingBox
     */
    public function setBoundingBox($boundingBox)
    {
        $this->bounding_box = $boundingBox;
    }

    /**
     * Get bounding_box
     *
     * @return array 
     */
    public function getBoundingBox()
    {
        return $this->bounding_box;
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

    /**
     * @var string $filename
     */
    private $filename;


    /**
     * Set filename
     *
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * Get filename
     *
     * @return string 
     */
    public function getFilename()
    {
        return $this->filename;
    }

    public function removeTiles()
    {
        $tilesDir = __DIR__ . '/../../../../web/tiles/' . $this->getAtlas()->getId() . '/' . $this->getId();
        $this->emptyDir($tilesDir, true);
    }

    private function emptyDir($dir, $remove = false) { 
        if (is_dir($dir)) { 
            $objects = scandir($dir); 
            foreach ($objects as $object) { 
                if ($object != "." && $object != "..") { 
                    if (filetype($dir."/".$object) == "dir") {
                        $this->emptyDir($dir."/".$object, true);
                    }
                    else {
                        unlink($dir."/".$object); 
                    }
                } 
            } 
            reset($objects); 
            if ($remove == true) {
                rmdir($dir);
            }
        } 
    } 
}
