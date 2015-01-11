<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Class Stats
 * @package AppBundle\Document
 * @MongoDB\Document(db="torrents", collection="statsTracker")
 */
class Stats{
    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $tracker;

    /**
     * @MongoDB\Int
     */
    protected $nb;

    /**
     * @MongoDB\Date
     */
    protected $lastIndexationDate;

    /**
     * @MongoDB\String
     */
    protected $stats;


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
     * Set nb
     *
     * @param int $nb
     * @return self
     */
    public function setNb($nb)
    {
        $this->nb = $nb;
        return $this;
    }

    /**
     * Get nb
     *
     * @return int $nb
     */
    public function getNb()
    {
        return $this->nb;
    }

    /**
     * Set lastIndexationDate
     *
     * @param date $lastIndexationDate
     * @return self
     */
    public function setLastIndexationDate($lastIndexationDate)
    {
        $this->lastIndexationDate = $lastIndexationDate;
        return $this;
    }

    /**
     * Get lastIndexationDate
     *
     * @return date $lastIndexationDate
     */
    public function getLastIndexationDate()
    {
        return $this->lastIndexationDate;
    }

    /**
     * @param mixed $stats
     */
    public function setStats($stats)
    {
        $this->stats = $stats;
    }

    /**
     * @return mixed
     */
    public function getStats()
    {
        return $this->stats;
    }


}
