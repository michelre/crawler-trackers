<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Torrent;

/**
 * Class Zetorrents
 * @package AppBundle\Document
 * @MongoDB\Document(db="torrents", collection="zetorrents")
 */
class Zetorrents extends Torrent {}
