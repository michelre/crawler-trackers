<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Btstorrent;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;


class BtstorrentCrawler
{

    private $torrentDAO;
    private $baseURL = "http://www.btstorrent.so";
    private $poolSize = 100;

    public function __construct($torrentDAO, $logger)
    {
        $this->torrentDAO = $torrentDAO;
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
        $categories = array(
            //1 => array('name' => 'Films', 'links' => []),
            //2 => array('name' => "Series", 'links' => []),
            3 => array('name' => 'Musique', 'links' => []),
            4 => array('name' => "Jeux", 'links' => []),
            5 => array('name' => "Logiciels", 'links' => []),
            6 => array('name' => "Anime", 'links' => []),
            7 => array('name' => "Misc", 'links' => []),
            8 => array('name' => "Porn", 'links' => []),
            9 => array('name' => "Ebooks", 'links' => []));
        foreach ($categories as $key => $values) {
            $crawler->filter('#subcat_ul_' . $key . ' li > a:not([rel])')->each(function ($node) use (&$categories, &$values, &$key) {
                array_push($values["links"], $this->baseURL . $node->attr('href'));
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
        $status_code = $response->getStatusCode();
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
            'complete' => function (CompleteEvent $event) use (&$torrents, &$category) {
                    $crawler = new Crawler($event->getResponse()->getBody()->getContents());
                    $crawler->filter('table.tor tr[id]')->each(function ($node) use(&$event, &$category){
                        $torrent = $this->_createTorrentObject($node, $category);
                        $this->torrentDAO->createOrUpdate($torrent);
                    });
                }
        ]);
        return $torrents;
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
        $title = $node->filter('.tname a')->text();
        $slug = $this->_slugify($title);
        $size = $node->filter('.tsize')->text();
        $seeds = $node->filter('.tseeds')->text();
        $leechs = $node->filter('.tpeers')->text();
        $urlTorrent = $this->baseURL . $node->filter('.tname a')->attr("href");
        preg_match("/tf(.*).html$/", $urlTorrent, $downloadLink);
        $downloadLink = $this->baseURL . '/torrentdownload.php?id=' . $downloadLink[1];
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
        return $torrent;
    }

}
