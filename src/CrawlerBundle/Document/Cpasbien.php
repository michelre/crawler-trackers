<?php


namespace CrawlerBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use CrawlerBundle\Document\Torrent;

/**
 * Class Cpasbien
 * @package ApiBundle\Document
 * @MongoDB\Document(db="torrents", collection="cpasbien")
 */
class Cpasbien extends Torrent {}
