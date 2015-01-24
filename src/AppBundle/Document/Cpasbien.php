<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Torrent;

/**
 * Class Cpasbien
 * @package ApiBundle\Document
 * @MongoDB\Document(db="torrents", collection="cpasbien")
 */
class Cpasbien extends Torrent {}
