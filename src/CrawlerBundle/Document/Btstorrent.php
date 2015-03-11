<?php


namespace CrawlerBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use CrawlerBundle\Document\Torrent;

/**
 * Class BtsTorrent
 * @package ApiBundle\Document
 * @MongoDB\Document(db="torrents", collection="btstorrent")
 */
class Btstorrent extends Torrent {}
