<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Class Torrent
 * @package AppBundle\Model
 * @MongoDB\Document
 */
class torrent {

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $slug;

    /**
     * @MongoDB\String
     */
    protected $title;

    /**
     * @MongoDB\String
     */
    protected $description;

    /**
     * @MongoDB\String
     */
    protected $downloadLink;

    /**
     * @MongoDB\String
     */
    protected $size;

    /**
     * @MongoDB\Int
     */
    protected $seeds;

    /**
     * @MongoDB\Int
     */
    protected $leechs;

    /**
     * @MongoDB\String
     */
    protected $url;

    /**
     * @MongoDB\String
     */
    protected $tracker;

    /**
     * @MongoDB\String
     */
    protected $category;


    /**
     * Get id
     *
     * @return id $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set slug
     *
     * @param string $slug
     * @return self
     */
    public function setSlug($slug)
    {
        $this->slug = $slug;
        return $this;
    }

    /**
     * Get slug
     *
     * @return string $slug
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Get title
     *
     * @return string $title
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string $description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set downloadLink
     *
     * @param string $downloadLink
     * @return self
     */
    public function setDownloadLink($downloadLink)
    {
        $this->downloadLink = $downloadLink;
        return $this;
    }

    /**
     * Get downloadLink
     *
     * @return string $downloadLink
     */
    public function getDownloadLink()
    {
        return $this->downloadLink;
    }

    /**
     * Set size
     *
     * @param string $size
     * @return self
     */
    public function setSize($size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * Get size
     *
     * @return string $size
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * Set seeds
     *
     * @param int $seeds
     * @return self
     */
    public function setSeeds($seeds)
    {
        $this->seeds = $seeds;
        return $this;
    }

    /**
     * Get seeds
     *
     * @return int $seeds
     */
    public function getSeeds()
    {
        return $this->seeds;
    }

    /**
     * Set leechs
     *
     * @param int $leechs
     * @return self
     */
    public function setLeechs($leechs)
    {
        $this->leechs = $leechs;
        return $this;
    }

    /**
     * Get leechs
     *
     * @return int $leechs
     */
    public function getLeechs()
    {
        return $this->leechs;
    }

    /**
     * Set url
     *
     * @param string $url
     * @return self
     */
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    /**
     * Get url
     *
     * @return string $url
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * Set tracker
     *
     * @param string $tracker
     * @return self
     */
    public function setTracker($tracker)
    {
        $this->tracker = $tracker;
        return $this;
    }

    /**
     * Get tracker
     *
     * @return string $tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * Set category
     *
     * @param string $category
     * @return self
     */
    public function setCategory($category)
    {
        $this->category = $category;
        return $this;
    }

    /**
     * Get category
     *
     * @return string $category
     */
    public function getCategory()
    {
        return $this->category;
    }
}
