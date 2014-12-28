<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Cpasbien;
use Goutte\Client;


class CpasbienCrawler{

    private $torrentDAO;
    private $baseURL = "http://www.cpasbien.pe";

    public function __construct($torrentDAO){
        $this->torrentDAO = $torrentDAO;
    }

    public function start(){
        $categories = ["films", "series", "musique", "ebook", "logiciels", "jeux-pc", "jeux-consoles"];
        foreach($categories as $category){
            $nbTotalPages = $this->_findNbPagesTotal($category);
            for($i = 0; $i < $nbTotalPages; $i++){
                $url = $this->baseURL . '/view_cat.php?categorie=' . $category .'&page=' . $i;
                $this->_extractTorrentsData($url, $category);
                usleep(50000);
            }
        }
    }

    protected  function _findNbPagesTotal($category){
        try {
            $client = new Client();
            $crawler = $client->request('GET', $this->baseURL . '/view_cat.php?categorie='. $category .'&page=1');
            $status_code = $client->getResponse()->getStatus();
            if ($status_code == 200) { // valid url and not reached depth limit yet
                $links = $crawler->filter("#pagination")
                                 ->children('a');
                return (int)$links->getNode(sizeof($links)-2)->textContent;
            }
            return 0;
        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $this->baseURL . '/torrents');
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $this->baseURL . '/torrents');
        }
    }

    protected  function _extractTorrentsData($url){
        try {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $status_code = $client->getResponse()->getStatus();
            if ($status_code == 200) { // valid url and not reached depth limit yet
                $category = $crawler->filter('#gauche h2 a')->text();
                $crawler->filter('#gauche > div')->each(function($node, $i) use(&$category){
                    if($node->attr('class') != null){
                        $torrent = $this->_createTorrentObject($node, $category);
                        $this->torrentDAO->createOrUpdate($torrent);
                    }
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
        $title = $node->filter('a.titre')->text();
        $slug  = $this->_slugify($title);
        $size = trim($node->filter('div.poid')->text());
        $seeds = $node->filter('div.up > span')->text();
        $leechs = $node->filter('div.down')->text();
        $url = $node->filter('a.titre')->attr('href');
        preg_match("/^(.*)\/(.*).html$/", $url, $urlRegex);
        $downloadLink = $this->baseURL . '/telechargement/' . $urlRegex[2] . '.torrent';
        $torrent = new Cpasbien();
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
