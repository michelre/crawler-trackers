<?php

namespace AppBundle\Controller;

use AppBundle\Crawler\BtstorrentCrawler;
use AppBundle\Crawler\SmartorrentCrawler;
use AppBundle\Crawler\CpasbienCrawler;
use AppBundle\Crawler\ZetorrentsCrawler;
use AppBundle\Crawler\OmgCrawler;
use AppBundle\Doctrine\StatsDAO;
use AppBundle\Doctrine\TorrentDAO;
use AppBundle\Stats\Stats;


class DefaultController
{
    protected $dm;
    protected $logger;

    public function __construct($dm, $logger){
        $this->dm = $dm;
        $this->logger = $logger;
    }

     public function dataAction($tracker, $categories = array()){
        $urlDAO = null; $torrentDAO = null;
        if(strcmp($tracker, "smartorrent") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Smartorrent');
            $crawler = new SmartorrentCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "cpasbien") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Cpasbien');
            $crawler = new CpasbienCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "zetorrents") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Zetorrents');
            $crawler = new ZetorrentsCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "btstorrent") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Btstorrent');
            $crawler = new BtstorrentCrawler($torrentDAO, $categories);
            $crawler->start();
        }
        if(strcmp($tracker, "omg") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Omg');
            $crawler = new OmgCrawler($torrentDAO);
            $crawler->start();
        }
        if($torrentDAO !== null){
            $statsDAO = new StatsDAO($this->dm);
            $stats = new Stats($statsDAO, $torrentDAO);
            $stats->calculate($tracker);
        }
        return "OK";
    }
}
