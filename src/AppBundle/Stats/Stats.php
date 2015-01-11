<?php

namespace AppBundle\Stats;

use AppBundle\AppBundle;
use AppBundle\Document\Smartorrent;
use Symfony\Component\DomCrawler\Crawler;


class Stats
{

    private $statsDAO;
    private $torrentDAO;

    public function __construct($statsDAO, $torrentDAO)
    {
        $this->statsDAO = $statsDAO;
        $this->torrentDAO = $torrentDAO;
    }

    public function calculate($tracker){
        $stats = new \AppBundle\Document\Stats();
        $currentDate = new \DateTime();
        $stats->setNb($this->torrentDAO->nbTotalTorrents());
        $stats->setTracker($tracker);
        $stats->setLastIndexationDate($currentDate->format("Y-m-d"));
        $stats->setStats("stats");
        $this->statsDAO->createOrUpdate($stats);
    }

}
