<?php


namespace AppBundle\Document;

use Doctrine\ODM\MongoDB\Mapping\Annotations as MongoDB;
use AppBundle\Document\Url;

/**
 * Class SmartorrentURL
 * @MongoDB\Document(db="url", collection="smartorrent")
 */
class SmartorrentURL extends Url {}
