<?php

namespace CrawlerBundle\Controller;

use CrawlerBundle\Crawler\BtstorrentCrawler;
use CrawlerBundle\Crawler\SmartorrentCrawler;
use CrawlerBundle\Crawler\CpasbienCrawler;
use CrawlerBundle\Crawler\ZetorrentsCrawler;
use CrawlerBundle\Crawler\OmgCrawler;
use CrawlerBundle\Doctrine\StatsDAO;
use CrawlerBundle\Doctrine\TorrentDAO;
use CrawlerBundle\Stats\Stats;


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
            $torrentDAO->removeAll();
            $crawler = new SmartorrentCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "cpasbien") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Cpasbien');
            $torrentDAO->removeAll();
            $crawler = new CpasbienCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "zetorrents") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Zetorrents');
            $torrentDAO->removeAll();
            $crawler = new ZetorrentsCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "btstorrent") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Btstorrent');
            if(empty($categories))
                $torrentDAO->removeAll();
            else
                $torrentDAO->removeAccordingToCategories($categories);
            $crawler = new BtstorrentCrawler($torrentDAO, $categories);
            $crawler->start();
        }
        if(strcmp($tracker, "omg") == 0){
            $torrentDAO = new TorrentDAO($this->dm, 'Omg');
            $torrentDAO->removeAll();
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

    public function getTorrentDetail($tracker, $url){
        if($tracker === "cpasbien"){
            $crawler = new CpasbienCrawler();
            $description = $crawler->getTorrentDetails($url);
        }
    }
}
