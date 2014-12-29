<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Smartorrent;
use Goutte\Client;


class SmartorrentCrawler{

    private $torrentDAO;
    private $baseURL = "http://www.smartorrent.com";

    public function __construct($torrentDAO){
        $this->torrentDAO = $torrentDAO;
    }

    public function start(){
        //$categories = $this->_categories();
        //foreach($categories as $key => $category){
            //$nbTotalPages = $this->_findNbPagesTotal($category["url"]);
            $nbTotalPages = $this->_findNbPagesTotal();
        var_dump($nbTotalPages);
            for($i = 1; $i <= 2; $i++){
                $url = $this->baseURL . '/torrents/' . $i .'/ordre/dd/'  ;
                $this->_extractTorrentsData($url, '');
                usleep(100000);
            }
        //}
    }

    protected function _categories(){
        return array(
            57              => array("name" => "3D", "url" => "http://smartorrent.com/?term=&cat=57&page=search"),
            55              => array("name" => "Spectacle/Concert", "url" => "http://smartorrent.com/?term=&cat=55&page=search"),
            26              => array("name" => "BluRay", "url" => "http://smartorrent.com/?term=&cat=26&page=search"),
            24              => array("name" => "Documentaires", "url" => "http://smartorrent.com/?term=&cat=24&page=search"),
            1               => array("name" => "Film", "url" => "http://smartorrent.com/?term=&cat=1&page=search"),
            49              => array("name" => "Film", "url" => "http://smartorrent.com/?term=&cat=49&page=search"),
            11              => array("name" => "Film", "url" => "http://smartorrent.com/?term=&cat=11&page=search"),
            29              => array("name" => "Film", "url" => "http://smartorrent.com/?term=&cat=29&page=search"),
            2               => array("name" => "Film", "url" => "http://smartorrent.com/?term=&cat=2&page=search"),
            17              => array("name" => "Film", "url" => "http://smartorrent.com/?term=&cat=17&page=search"),
            33              => array("name" => "Séries", "url" => "http://smartorrent.com/?term=&cat=33&page=search"),
            43              => array("name" => "Séries", "url" => "http://smartorrent.com/?term=&cat=43&page=search"),
            19              => array("name" => "Animés", "url" => "http://smartorrent.com/?term=&cat=19&page=search"),
            13              => array("name" => "Jeux PC", "url" => "http://smartorrent.com/?term=&cat=13&page=search"),
            20              => array("name" => "Jeux Consoles", "url" => "http://smartorrent.com/?term=&cat=20&page=search"),
            42              => array("name" => "Jeux Consoles", "url" => "http://smartorrent.com/?term=&cat=42&page=search"),
            14              => array("name" => "Jeux Consoles", "url" => "http://smartorrent.com/?term=&cat=14&page=search"),
            54              => array("name" => "Musique", "url" => "http://smartorrent.com/?term=&cat=54&page=search"),
            3               => array("name" => "Musique", "url" => "http://smartorrent.com/?term=&cat=3&page=search"),
            12              => array("name" => "Logiciel", "url" => "http://smartorrent.com/?term=&cat=12&page=search"),
            6               => array("name" => "Divers", "url" => "http://smartorrent.com/?term=&cat=6&page=search"),
            46              => array("name" => "Téléphones", "url" => "http://smartorrent.com/?term=&cat=46&page=search"),
            5               => array("name" => "Ebooks", "url" => "http://smartorrent.com/?term=&cat=5&page=search"),
            18              => array("name" => "Divers", "url" => "http://smartorrent.com/?term=&cat=18&page=search"),
        );
    }

    protected  function _findNbPagesTotal($url = null){
        try {
            $client = new Client();
            $url = ($url != null) ? $url : "http://smartorrent.com/torrents/1/ordre/dd/";
            $crawler = $client->request('GET', $url);
            $status_code = $client->getResponse()->getStatus();
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

    protected  function _extractTorrentsData($url, $category){
        try {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $status_code = $client->getResponse()->getStatus();
            if ($status_code == 200) { // valid url and not reached depth limit yet
                $crawler->filter('table#parcourir tbody tr')->each(function($node, $i) use(&$category){
                        $torrent = $this->_createTorrentObject($node, $category);
                        $this->torrentDAO->createOrUpdate($torrent);
                });
            }
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
        var_dump($downloadLink);
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
