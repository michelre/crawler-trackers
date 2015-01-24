<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Torrent;

/**
 * Class Omg
 * @package ApiBundle\Document
 * @MongoDB\Document(db="torrents", collection="omg")
 */
class Omg extends Torrent {}
