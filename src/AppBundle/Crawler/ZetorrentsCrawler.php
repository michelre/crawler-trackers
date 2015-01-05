<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Zetorrents;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;



class ZetorrentsCrawler{

    private $torrentDAO;
    private $baseURL = "http://www.zetorrents.com/";
    private $poolSize = 100;

    public function __construct($torrentDAO){
        $this->torrentDAO = $torrentDAO;
    }

    public function start(){
        $categories = $this->_findCategories();
        foreach($categories as $key => $value){
            foreach($value["links"] as $link){
                $nbTotalPages = $this->_findNbPagesTotal($link);
                $i = 1;
                while ($i < $nbTotalPages) {
                    $requests = $this->_createPoolRequests($i, $nbTotalPages, $link);
                    $this->_extractTorrentsData($requests, $value["name"]);
                    $i += sizeof($requests);
                    $this->torrentDAO->flush();
                    $this->torrentDAO->clear();
                }
                if($i >= $nbTotalPages){
                    $request = [$this->_createRequest($link . '/page:' . $nbTotalPages)];
                    $this->_extractTorrentsData($request, $value["name"]);
                    $this->torrentDAO->flush();
                    $this->torrentDAO->clear();
                }
            }
        }
    }

    protected function _findCategories()
    {
        $categories = array(
            1 => array('name' => 'Films', 'links' => ["http://www.zetorrents.com/torrents/all/1/films",
                                                      "http://www.zetorrents.com/torrents/all/493/films-en-vostfr",
                                                      "http://www.zetorrents.com/torrents/find/title:DVDRIP",
                                                      "http://www.zetorrents.com/torrents/find/title:BluRay"]),
            2 => array('name' => "Series", 'links' => ["http://www.zetorrents.com/torrents/all/733/series-de-a-a-f",
                                                       "http://www.zetorrents.com/torrents/all/734/series-de-g-a-l",
                                                       "http://www.zetorrents.com/torrents/all/735/series-de-m-a-s",
                                                       "http://www.zetorrents.com/torrents/all/737/series-de-t-a-z",
                                                       "http://www.zetorrents.com/torrents/all/732/mangas-et-animes" ]),
            3 => array('name' => 'Musique', 'links' => ["http://www.zetorrents.com/torrents/all/3/musique"]),
            4 => array('name' => "Jeux PC", 'links' => ["http://www.zetorrents.com/torrents/all/5/jeux-pc"]),
            5 => array('name' => "Jeux consoles", 'links' => ["http://www.zetorrents.com/torrents/all/6/jeux-consoles"]),
            6 => array('name' => "Logiciels", 'links' => ["http://www.zetorrents.com/torrents/all/4/logiciels"]),
            7 => array('name' => "Ebooks", 'links' => ["http://www.zetorrents.com/torrents/all/348/ebooks"]));
        return $categories;

    }

    protected function _createPoolRequests($i, $total, $link)
    {
        $requests = [];
        $n = (($total - $i) < $this->poolSize) ? ($total - $i) : $this->poolSize;
        for ($j = $i; $j <= ($n + $i); $j++) {
            $url = $link . '/page:' . $j;
            array_push($requests, $this->_createRequest($url));
        }
        return $requests;
    }

    protected function _createRequest($url){
        $client = new \GuzzleHttp\Client();
        return $client->createRequest('GET', $url);
    }

    protected  function _findNbPagesTotal($url){
        $client = new Client();
        $response = $client->get($url);
        $crawler = new Crawler($response->getBody()->getContents());
        preg_match("/\/(\d.*)/", $crawler->filter(".pagination span")->last()->text(), $nbPages);
        return (int)$nbPages[1];
    }

    protected function _extractTorrentsData($requests, $category)
    {
        $client = new Client();
        Pool::send($client, $requests, [
            'complete' => function (CompleteEvent $event) use(&$torrents, &$category) {
                    $crawler = new Crawler($event->getResponse()->getBody()->getContents());
                    $crawler->filter('.content-list-torrent table tbody tr')->each(function ($node) use(&$category){
                        $torrent = $this->_createTorrentObject($node, $category);
                        $this->torrentDAO->createOrUpdate($torrent);
                    });
                }
        ]);
        return $torrents;
    }

    protected function _slugify($str, $replace=array(), $delimiter='-') {
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }

        $clean = $str;//iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    protected function _createTorrentObject($node, $category){
        $title = $node->filter('td:nth-child(2) a')->text();
        $slug  = $this->_slugify($title);
        $size = $node->filter('td:nth-child(3) span')->text();
        $seeds = $node->filter('td:nth-child(4) span')->text();
        $leechs = $node->filter('td:nth-child(5) span')->text();
        $url = $this->baseURL . $node->filter('td:nth-child(2) a')->attr('href');
        $downloadLink = $this->_getDownloadLink($url);
        $torrent = new Zetorrents();
        $torrent->setSlug($slug);
        $torrent->setTitle($title);
        $torrent->setCategory($category);
        $torrent->setSize($size);
        $torrent->setSeeds($seeds);
        $torrent->setLeechs($leechs);
        $torrent->setUrl($url);
        $torrent->setDownloadLink($downloadLink);
        $torrent->setTracker("zetorrents");
        return $torrent;
    }

    private function _getDownloadLink($url)
    {
        $client = new Client();
        $response = $client->get($url);
        $crawler = new Crawler($response->getBody()->getContents());
        return $this->baseURL . $crawler->filter("#download-link a")->attr('href');
    }

}
