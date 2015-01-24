<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Torrent;

/**
 * Class Smartorrent
 * @package ApiBundle\Document
 * @MongoDB\Document(db="torrents", collection="smartorrent")
 */
class Smartorrent extends Torrent {}
