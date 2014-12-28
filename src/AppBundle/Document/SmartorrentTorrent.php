<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Torrent;

/**
 * Class SmartorrentTorrent
 * @package AppBundle\Document
 * @MongoDB\Document(db="torrents", collection="smartorrent")
 */
class SmartorrentTorrent extends Torrent {}