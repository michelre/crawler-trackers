<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Omg;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;
use Cocur\Slugify\Slugify;



class OmgCrawler{

    private $torrentDAO;
    private $baseURL = "http://www.omgtorrent.com";
    private $poolSize = 100;

    public function __construct($torrentDAO){
        $this->torrentDAO = $torrentDAO;
    }

    public function start(){
        $nbTotalPages = $this->_findNbPagesTotal();
        $i = 1;
        while ($i <= $nbTotalPages) {
            $requests = $this->_createPoolRequests($i, $nbTotalPages);
            $this->_extractTorrentsData($requests);
            $i += sizeof($requests);
            $this->torrentDAO->flush();
            $this->torrentDAO->clear();
        }
    }

    protected function _createPoolRequests($i, $total)
    {
        $requests = [];
        $n = (($total - $i) < $this->poolSize) ? ($total - $i) : $this->poolSize;
        for ($j = $i; $j <= ($n + $i); $j++) {
            $url = $this->baseURL . '/torrents/?order=id&orderby=desc&page=' . $j;
            array_push($requests, $this->_createRequest($url));
        }
        return $requests;
    }

    protected function _createRequest($url){
        $client = new \GuzzleHttp\Client();
        return $client->createRequest('GET', $url);
    }

    protected  function _findNbPagesTotal(){
        $client = new Client();
        $response = $client->get($this->baseURL . '/torrents/?order=id&orderby=desc&page=1');
        $crawler = new Crawler($response->getBody()->getContents());
        $links = $crawler->filter("div.nav")->children('a');
        return (int)$links->getNode(sizeof($links)-2)->textContent;
    }

    protected function _extractTorrentsData($requests)
    {
        $client = new Client();
        Pool::send($client, $requests, [
            'complete' => function (CompleteEvent $event) use(&$torrents) {
                    $crawler = new Crawler($event->getResponse()->getBody()->getContents());
                    $crawler->filter('#corps .table_corps tr:not([class="table_entete"])')->each(function ($node){
                        $torrent = $this->_createTorrentObject($node);
                        $this->torrentDAO->createOrUpdate($torrent);
                    });
                }
        ]);
        return $torrents;
    }

    protected function _createTorrentObject($node){
        $slugify = new Slugify();
        $title = $node->filter('td:nth-child(2) a')->text();
        $size = $node->filter('td:nth-child(3)')->text();
        $seeds = preg_replace("/,/", "", $node->filter('td.sources')->text());
        $leechs = preg_replace("/,/", "", $node->filter('td.clients')->text());
        $url = $this->baseURL . $node->filter('td:nth-child(2) a')->attr('href');
        preg_match("/_(\d.*).html$/", $url, $urlRegex);
        $downloadLink = $this->baseURL . '/clic_dl.php?id=' . $urlRegex[1];
        $category = $node->filter('td:nth-child(1)')->text();
        $slug = $slugify->slugify($title . ' ' . $urlRegex[1]);
        $torrent = new Omg();
        $torrent->setSlug($slug);
        $torrent->setTitle($title);
        $torrent->setCategory($category);
        $torrent->setSize($size);
        $torrent->setSeeds($seeds);
        $torrent->setLeechs($leechs);
        $torrent->setUrl($url);
        $torrent->setDownloadLink($downloadLink);
        $torrent->setTracker("omg");
        return $torrent;
    }

}
