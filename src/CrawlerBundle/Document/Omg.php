<?php


namespace CrawlerBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use CrawlerBundle\Document\Torrent;

/**
 * Class Omg
 * @package ApiBundle\Document
 * @MongoDB\Document(db="torrents", collection="omg")
 */
class Omg extends Torrent {}
