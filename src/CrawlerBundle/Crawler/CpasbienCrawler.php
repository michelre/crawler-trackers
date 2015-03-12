<?php

namespace CrawlerBundle\Crawler;

use CrawlerBundle\CrawlerBundle;
use CrawlerBundle\Document\Cpasbien;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;
use Cocur\Slugify\Slugify;



class CpasbienCrawler{

    private $torrentDAO;
    private $baseURL = "http://www.cpasbien.pe";
    private $poolSize = 100;

    public function __construct($torrentDAO = null){
        $this->torrentDAO = $torrentDAO;
    }

    public function start(){
        $categories = ["films", "series", "musique", "ebook", "logiciels", "jeux-pc", "jeux-consoles"];
        foreach($categories as $category){
            $nbTotalPages = $this->_findNbPagesTotal($category);
            $i = 0;
            while ($i < $nbTotalPages) {
                $requests = $this->_createPoolRequests($i, $nbTotalPages, $category);
                $this->_extractTorrentsData($requests);
                $i += sizeof($requests);
                $this->torrentDAO->flush();
                $this->torrentDAO->clear();
            }
            if($i >= $nbTotalPages){
                $request = [$this->_createRequest($this->baseURL . '/view_cat.php?categorie=' . $category .'&page=' . $nbTotalPages)];
                $this->_extractTorrentsData($request);
                $this->torrentDAO->flush();
                $this->torrentDAO->clear();
            }
        }
    }

    protected function _createPoolRequests($i, $total, $category)
    {
        $requests = [];
        $n = (($total - $i) < $this->poolSize) ? ($total - $i) : $this->poolSize;
        for ($j = $i; $j < ($i + $n); $j++) {
            $url = $this->baseURL . '/view_cat.php?categorie=' . $category .'&page=' . $j;
            array_push($requests, $this->_createRequest($url));
        }
        return $requests;
    }

    protected function _createRequest($url){
        $client = new \GuzzleHttp\Client();
        return $client->createRequest('GET', $url);
    }

    protected  function _findNbPagesTotal($category){
        $client = new Client();
        $response = $client->get($this->baseURL . '/view_cat.php?categorie='. $category .'&page=1');
        $crawler = new Crawler($response->getBody()->getContents());
        $links = $crawler->filter("#pagination")->children('a');
        return (int)$links->getNode(sizeof($links)-2)->textContent;
    }

    protected function _extractTorrentsData($requests)
    {
        $client = new Client();
        Pool::send($client, $requests, [
            'complete' => function (CompleteEvent $event) use(&$torrents) {
                    $crawler = new Crawler($event->getResponse()->getBody()->getContents());
                    $crawler->filter('#gauche div[class="ligne0"], #gauche div[class="ligne1"]')->each(function ($node){
                        $torrent = $this->_createTorrentObject($node);
                        $this->torrentDAO->createOrUpdate($torrent);
                    });
                }
        ]);
        return $torrents;
    }

    protected function _createTorrentObject($node){
        $slugify = new Slugify();
        $title = $node->filter('a.titre')->text();
        $slug  = $slugify->slugify($title);
        $size = trim($node->filter('div.poid')->text());
        $seeds = $node->filter('div.up > span')->text();
        $leechs = $node->filter('div.down')->text();
        $url = $node->filter('a.titre')->attr('href');
        preg_match("/^(.*)\/(.*).html$/", $url, $urlRegex);
        $downloadLink = $this->baseURL . '/telechargement/' . $urlRegex[2] . '.torrent';
        preg_match("/dl-torrent\/(.*)\/.*\//", $url, $category);
        $category = $category[1];
        $torrent = new Cpasbien();
        $torrent->setSlug($slug);
        $torrent->setTitle($title);
        $torrent->setCategory($category);
        $torrent->setSize($size);
        $torrent->setSeeds($seeds);
        $torrent->setLeechs($leechs);
        $torrent->setUrl($url);
        $torrent->setDownloadLink($downloadLink);
        $torrent->setTracker("cpasbien");
        return $torrent;
    }

    public function getTorrentDetails($url){
        try{
            $client = new Client();
            $request = $client->createRequest('GET', $url);
            $response = $client->send($request);
            $crawler = new Crawler($response->getBody()->getContents());
            return $crawler->filter('#textefiche p:nth-child(2)')->text();
        }catch(RequestException $e){
            return '';
        }
    }

}
