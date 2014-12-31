<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Smartorrent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;


class SmartorrentCrawler{

    private $torrentDAO;
    private $baseURL = "http://www.smartorrent.com";
    private $poolSize = 3;

    public function __construct($torrentDAO){
        $this->torrentDAO = $torrentDAO;
    }

    public function start(){
        $nbTotalPages = $this->_findNbPagesTotal();
        //for($i = 1; $i <= $nbTotalPages; $i++){
        $i = 1;
        while($i <= $nbTotalPages){
            $requests = $this->_createPoolRequests($i, 10);
            $this->_extractTorrentsData($requests);
            usleep(50000);
            $i += sizeof($requests);
        }
    }

    protected  function _createPoolRequests($i, $total){
        $client = new \GuzzleHttp\Client();
        $requests = [];
        $n = (($total - $i) < $this->poolSize) ? ($total - $i) : $this->poolSize;
        for($i = 0; $i < $n; $i++){
            $url = $this->baseURL . '/torrents/' . $i .'/ordre/dd/';
            array_push($requests, $client->createRequest('GET', $url));
        }
        return $requests;
    }

    protected  function _findNbPagesTotal($url = null){
        try {
            $client = new Client();
            $url = ($url != null) ? $url : "http://smartorrent.com/torrents/1/ordre/dd/";
            $response = $client->get($url);
            $status_code = $response->getStatusCode();
            $crawler = new Crawler($response->getBody()->getContents());
            if ($status_code == 200) { // valid url and not reached depth limit yet
                $lastLink = $crawler->filter("#pagination a")->last()->attr('href');
                preg_match("/^\/torrents\/(\d.*)\/ordre/", $lastLink, $regexLastLink);
                return (int)$regexLastLink[1];
            }
            return 0;
        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $this->baseURL . '/torrents');
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $this->baseURL . '/torrents');
        }
    }

    protected  function _extractTorrentsData($requests){
        try {
            Pool::send(new Client(), $requests, [
                'complete' => function(CompleteEvent $event){
                        $crawler = new Crawler($event->getResponse()->getBody()->getContents());
                        $crawler->filter('table#parcourir tbody tr')->each(function($node, $i) use(&$category){
                            $torrent = $this->_createTorrentObject($node, $category);
                            $this->torrentDAO->createOrUpdate($torrent);
                        });
                    }
            ]);

        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $this->baseURL . '/torrents');
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $this->baseURL . '/torrents');
        }
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
        $title = trim($node->filter('td.nom > a')->text());
        $slug  = $this->_slugify($title);
        $size = $node->filter('td.completed')->text();
        //$size = $node->filter('td.taille')->text();
        $seeds = $node->filter('td.seed')->text();
        $leechs = $node->filter('td.leech')->text();
        $url = $node->filter('td.nom > a')->attr('href');
        preg_match("/\/(\d.*)\/$/", $url, $urlRegex);
        $downloadLink = $this->baseURL . '/?page=download&tid=' . $urlRegex[1];
        $torrent = new Smartorrent();
        $torrent->setSlug($slug);
        $torrent->setTitle($title);
        $torrent->setCategory($category);
        $torrent->setSize($size);
        $torrent->setSeeds($seeds);
        $torrent->setLeechs($leechs);
        $torrent->setUrl($url);
        $torrent->setDownloadLink($downloadLink);
        return $torrent;
    }

}
