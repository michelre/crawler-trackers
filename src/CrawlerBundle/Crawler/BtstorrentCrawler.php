<?php

namespace CrawlerBundle\Crawler;

use CrawlerBundle\Document\Btstorrent;
use CrawlerBundle\Services\BodyReader;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;
use Cocur\Slugify\Slugify;


class BtstorrentCrawler
{

    private $torrentDAO;
    private $baseURL = "http://www.btscene.cc";
    private $poolSize = 100;
    private $bodyReader;
    private $categories;

    public function __construct($torrentDAO = null, $categories = array())
    {
        $this->torrentDAO = $torrentDAO;
        $this->bodyReader = new BodyReader();
        $this->categories = $categories;
    }

    public function start()
    {
        $categories = $this->_findCategories();
        foreach ($categories as $category) {
            foreach ($category["links"] as $link) {
                $nbTotalPages = $this->_findNbPagesTotal($link);
                $i = 1;
                while ($i < $nbTotalPages) {
                    $requests = $this->_createPoolRequests($i, $nbTotalPages, $link);
                    $this->_extractTorrentsData($requests, $category["name"]);
                    $i += sizeof($requests);
                    $this->torrentDAO->flush();
                    $this->torrentDAO->clear();
                    unset($requests);
                }
                if ($i >= $nbTotalPages || $nbTotalPages == 0) {
                    $request = [$this->_createRequest($link)];
                    $this->_extractTorrentsData($request, $category["name"]);
                    $this->torrentDAO->flush();
                    $this->torrentDAO->clear();
                }
            }
        }
    }

    protected function _findCategories()
    {
        $client = new Client();
        $response = $client->get($this->baseURL . '/browse/');
        $crawler = new Crawler($response->getBody()->getContents());
        $allCategories = array(
            1 => array('name' => 'Films', 'links' => []),
            2 => array('name' => "Series", 'links' => []),
            3 => array('name' => 'Musique', 'links' => []),
            4 => array('name' => "Jeux", 'links' => []),
            5 => array('name' => "Logiciels", 'links' => []),
            6 => array('name' => "Anime", 'links' => []),
            7 => array('name' => "Misc", 'links' => []),
            8 => array('name' => "Porn", 'links' => []),
            9 => array('name' => "Ebooks", 'links' => []));
        if(sizeof($this->categories) > 0)
            $categories = array_filter($allCategories, function($category){
                return in_array($category['name'], $this->categories);
            });
        else
            $categories = $allCategories;
        foreach ($categories as $key => $values) {
            $crawler->filter('#subcat_ul_' . $key . ' li > a:not([rel])')->each(function ($node) use (&$categories, &$values, &$key) {
                if(preg_match('#^\/subcat\/#', $node->attr('href'))){
                    array_push($values["links"], $this->baseURL . $node->attr('href'));
                }
                $categories[$key] = $values;
            });
        }
        return $categories;
    }

    protected function _createPoolRequests($i, $total, $link)
    {
        $requests = [];
        $n = (($total - $i) < $this->poolSize) ? ($total - $i) : $this->poolSize;
        for ($j = $i; $j <= ($n + $i); $j++) {
            $url = $link . 'page/' . $j . '/';
            array_push($requests, $this->_createRequest($url));
        }
        return $requests;
    }

    protected function _createRequest($url)
    {
        $client = new \GuzzleHttp\Client();
        return $client->createRequest('GET', $url);
    }

    protected function _findNbPagesTotal($url = null)
    {
        $client = new Client();
        $response = $client->get($url);
        $crawler = new Crawler($response->getBody()->getContents());
        if ($crawler->filter(".pagination ul")->count() > 0) {
            $lastPageNode = $crawler->filter(".pagination ul li")->eq(sizeof($crawler->filter(".pagination ul")->children()) - 3);
            return (int)$lastPageNode->filter('a')->text();
        }
        return 0;
    }

    protected function _extractTorrentsData($requests, $category)
    {
        $client = new Client();
        Pool::send($client, $requests, [
            'complete' => function (CompleteEvent $event) use (&$category) {
                    $content = $event->getResponse()->getBody()->getContents();
                    $crawler = new Crawler($content);
                    $crawler->filter('table.tor tr[id]')->each(function ($node) use(&$event, &$category){
                        $torrent = $this->_createTorrentObject($node, $category);
                        $this->torrentDAO->createOrUpdate($torrent);
                        unset($content);
                        unset($torrent);
                    });
                    unset($crawler);
                }
        ]);
    }

    protected function _createTorrentObject($node, $category)
    {
        $slugify = new Slugify();
        $title = $node->filter('.tname a')->text();
        $size = $node->filter('.tsize')->text();
        $seeds = $node->filter('.tseeds')->text();
        $leechs = $node->filter('.tpeers')->text();
        $urlTorrent = $this->baseURL . $node->filter('.tname a')->attr("href");
        preg_match("/tf(.*).html$/", $urlTorrent, $downloadLinkArray);
        $downloadLink = $this->baseURL . '/torrentdownload.php?id=' . $downloadLinkArray[1];
        $slug = $slugify->slugify($title . ' ' . $downloadLinkArray[1]);
        $torrent = new Btstorrent();
        $torrent->setSlug($slug);
        $torrent->setTitle($title);
        $torrent->setCategory($category);
        $torrent->setSize($size);
        $torrent->setSeeds($seeds);
        $torrent->setLeechs($leechs);
        $torrent->setUrl($urlTorrent);
        $torrent->setDownloadLink($downloadLink);
        $torrent->setTracker("btstorrent");
        $torrent->setVisible(true);
        return $torrent;
    }

    public function getTorrentDetails($url){
        try{
            $client = new Client();
            $request = $client->createRequest('GET', $url);
            $response = $client->send($request);
            $crawler = new Crawler($response->getBody()->getContents());
            $description = $crawler->filter(".analytics_container.descr")->reduce(function(Crawler $node){
                return $node->filter('h3')->text() === "Description";
            });
            if(sizeof($description) > 0)
                return array("description" => $description->text(), "img" => "");
            else
                throw new Exception();
        }catch(\InvalidArgumentException $e){
            return array("description" => "Pas d'information / No information available", "img" => "");
        }
    }

}
