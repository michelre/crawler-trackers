<?php

namespace AppBundle\CrawlerURL;

use AppBundle\AppBundle;
use AppBundle\Document\SmartorrentURL;
use Goutte\Client;


class CrawlerCpasbienURL{

    private $urlDAO;
    private $baseURL = "http://www.cpasbien.pe";

    public function __construct($urlDAO){
        $this->urlDAO = $urlDAO;
    }

    public function start(){
        $categories = ["films", "series", "musique", "ebook", "logiciels", "jeux-pc", "jeux-consoles"];
        foreach($categories as $category){
            $nbTotalPages = $this->_findNbPagesTotal($category);
            for($i = 0; $i < $nbTotalPages; $i++){
                $url = $this->baseURL . '/view_cat.php?categorie=' . $category .'&page=' . $i;
                $this->_insertLinks($url);
                usleep(50000);
            }
        }
    }

    protected  function _findNbPagesTotal($category){
        try {
            $client = new Client();
            $crawler = $client->request('GET', $this->baseURL . '/view_cat.php?categorie=films&page=1');
            $status_code = $client->getResponse()->getStatus();
            if ($status_code == 200) { // valid url and not reached depth limit yet
                $content_type = $client->getResponse()->getHeader('Content-Type');
                if (strpos($content_type, 'text/html') !== false) {
                    $lastPageLink = $crawler
                                        ->filter("#pagination > a")
                                        ->last()
                                        ->attr('href');
                    preg_match('@\/torrents\/(.*)\/ordre@', $lastPageLink, $nbTotalPages);
                    return (int)$nbTotalPages[1];
                }
            }
            return 0;
        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $this->baseURL . '/torrents');
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $this->baseURL . '/torrents');
        }
    }

    protected  function _insertLinks($url){
        try {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $status_code = $client->getResponse()->getStatus();
            if ($status_code == 200) { // valid url and not reached depth limit yet
                $content_type = $client->getResponse()->getHeader('Content-Type');
                if (strpos($content_type, 'text/html') !== false) {
                    $crawler->filter('.boxContent table td.nom a')->each(function($node, $i){
                        $url = $node->attr('href');
                        if(preg_match('@\/torrent\/Torrent@', $url)){
                            $this->urlDAO->createOrUpdate($this->_createURLObject($url, false));
                        }
                    });
                }
            }
        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $this->baseURL . '/torrents');
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $this->baseURL . '/torrents');
        }
    }

    protected function _createURLObject($url, $visited){
        $smartorrentUrl = new SmartorrentURL();
        $smartorrentUrl->setUri($url);
        $smartorrentUrl->setVisited($visited);
        $smartorrentUrl->setLastIndexationDate(date("Y-m-d"));
        return $smartorrentUrl;
    }

}
