<?php

namespace CrawlerBundle\Crawler;

use CrawlerBundle\CrawlerBundle;
use CrawlerBundle\Document\Smartorrent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;
use Cocur\Slugify\Slugify;


class SmartorrentCrawler
{

    private $torrentDAO;
    private $baseURL = "http://www.smartorrent.com";
    private $poolSize = 100;

    public function __construct($torrentDAO = null)
    {
        $this->torrentDAO = $torrentDAO;
    }

    public function start()
    {
        $nbTotalPages = $this->_findNbPagesTotal();
        $i = 1;
        while ($i < $nbTotalPages) {
            $requests = $this->_createPoolRequests($i, $nbTotalPages);
            $this->_extractTorrentsData($requests);
            $i += sizeof($requests);
            $this->torrentDAO->flush();
            $this->torrentDAO->clear();
        }
        if($i >= $nbTotalPages){
            $requests = [$this->_createRequest($this->baseURL . '/torrents/' . $nbTotalPages . '/ordre/dd')];
            $this->_extractTorrentsData($requests);
            $this->torrentDAO->flush();
            $this->torrentDAO->clear();
        }
    }

    protected function _createPoolRequests($i, $total)
    {
        $requests = [];
        $n = (($total - $i) < $this->poolSize) ? ($total - $i) : $this->poolSize;
        for ($j = $i; $j < ($i + $n); $j++) {
            $url = $this->baseURL . '/torrents/' . $j . '/ordre/dd/';
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
                        $this->torrentDAO->createOrUpdate($torrent);
                    });
                }
        ]);
        return $torrents;
    }

    protected function _createTorrentObject($node, $category)
    {
        $slugify = new Slugify();
        $title = trim($node->filter('td.nom > a')->text());
        $size = $node->filter('td.completed')->text();
        $seeds = $node->filter('td.seed')->text();
        $leechs = $node->filter('td.leech')->text();
        $url = $node->filter('td.nom > a')->attr('href');
        preg_match("/\/(\d.*)\/$/", $url, $urlRegex);
        $downloadLink = $this->baseURL . '/?page=download&tid=' . $urlRegex[1];
        $category = $this->_getCategoryCorrespondance($node->filter('td.nom > div')->attr('class'));
        $slug = $slugify->slugify($title . ' ' . $urlRegex[1]);
        $torrent = new Smartorrent();
        $torrent->setSlug($slug);
        $torrent->setTitle($title);
        $torrent->setCategory($category);
        $torrent->setSize($size);
        $torrent->setSeeds($seeds);
        $torrent->setLeechs($leechs);
        $torrent->setUrl($url);
        $torrent->setDownloadLink($downloadLink);
        $torrent->setTracker("smartorrent");
        return $torrent;
    }

    protected function _getCategoryCorrespondance($className){
        $categories = array(
          'global_misc'   => 'Ebook',
          'global_dvdrip' => 'Films',
          'global_pc'     => 'Applications',
          'global_dvdrip' => 'Films',
          'global_music'  => 'Musique',
          'global_tvrip'  => 'Série',
          'global_ebook'  => 'Jeux',
        );

        return (array_key_exists($className, $categories)) ? $categories[$className] : $className;
    }

    public function getTorrentDetails($url){
        try{
            $client = new Client();
            $request = $client->createRequest('GET', $url);
            $response = $client->send($request);
            $crawler = new Crawler($response->getBody()->getContents());
            return htmlentities($crawler->filter('.bbcode_centre')->html());
        }catch(RequestException $e){
            return '';
        }
    }

}
