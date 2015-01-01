<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Smartorrent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;


class SmartorrentCrawler
{

    private $torrentDAO;
    private $baseURL = "http://www.smartorrent.com";
    private $poolSize = 50;

    public function __construct($torrentDAO)
    {
        $this->torrentDAO = $torrentDAO;
    }

    public function start()
    {
        $nbTotalPages = $this->_findNbPagesTotal();
        $i = 1;
        while ($i < $nbTotalPages) {
            $requests = $this->_createPoolRequests($i, $nbTotalPages);
            $torrents = $this->_extractTorrentsData($requests);
            $this->_addTorrentsToDB($torrents);
            $i += sizeof($requests);
        }
        if($i == $nbTotalPages){
            $request = [$this->_createRequest($this->baseURL . '/torrents/' . $nbTotalPages . '/ordre/dd/')];
            $torrents = $this->_extractTorrentsData($request);
            $this->_addTorrentsToDB($torrents);
        }
    }

    protected function _createPoolRequests($i, $total)
    {
        $requests = [];
        $n = (($total - $i) < $this->poolSize) ? ($total - $i) : $this->poolSize;
        for ($j = 0; $j < $n; $j++) {
            $url = $this->baseURL . '/torrents/' . ($j + $i) . '/ordre/dd/';
            array_push($requests, $this->_createRequest($url));
        }
        return $requests;
    }

    protected function _createRequest($url){
        $client = new \GuzzleHttp\Client();
        return $client->createRequest('GET', $url);
    }

    protected function _findNbPagesTotal($url = null)
    {
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

    protected function _extractTorrentsData($requests)
    {
        $client = new Client();
        $torrents = [];
        Pool::send($client, $requests, [
            'complete' => function (CompleteEvent $event) use(&$torrents) {
                    $crawler = new Crawler($event->getResponse()->getBody()->getContents());
                    $crawler->filter('table#parcourir tbody tr')->each(function ($node) use(&$torrents) {
                        $torrent = $this->_createTorrentObject($node, '');
                        array_push($torrents, $torrent);
                    });
                }
        ]);
        return $torrents;
    }

    protected function _addTorrentsToDB($torrents){
        foreach($torrents as $torrent){
            $this->torrentDAO->createOrUpdate($torrent);
        }
        $this->torrentDAO->flush();
    }

    protected function _slugify($str, $replace = array(), $delimiter = '-')
    {
        if (!empty($replace)) {
            $str = str_replace((array)$replace, ' ', $str);
        }

        $clean = $str; //iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    protected function _createTorrentObject($node, $category)
    {
        $title = trim($node->filter('td.nom > a')->text());
        $slug = $this->_slugify($title);
        $size = $node->filter('td.completed')->text();
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
