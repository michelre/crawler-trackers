<?php


namespace CrawlerBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use CrawlerBundle\Document\Torrent;

/**
 * Class Smartorrent
 * @package ApiBundle\Document
 * @MongoDB\Document(db="torrents", collection="smartorrent")
 */
class Smartorrent extends Torrent {}
