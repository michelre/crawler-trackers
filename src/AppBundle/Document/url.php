<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;

/**
 * Class Url
 * @package AppBundle\Model
 * @MongoDB\Document
 */
class url {

    /**
     * @MongoDB\Id
     */
    protected $id;

    /**
     * @MongoDB\String
     */
    protected $uri;

    /**
     * @MongoDB\String
     */
    protected $lastIndexationDate;

    /**
     * @MongoDB\Boolean
     */
    protected $visited;

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $lastIndexationDate
     */
    public function setLastIndexationDate($lastIndexationDate)
    {
        $this->lastIndexationDate = $lastIndexationDate;
    }

    /**
     * @return mixed
     */
    public function getLastIndexationDate()
    {
        return $this->lastIndexationDate;
    }

    /**
     * @param mixed $uri
     */
    public function setUri($uri)
    {
        $this->uri = $uri;
    }

    /**
     * @return mixed
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param mixed $visited
     */
    public function setVisited($visited)
    {
        $this->visited = $visited;
    }

    /**
     * @return mixed
     */
    public function getVisited()
    {
        return $this->visited;
    }


}
