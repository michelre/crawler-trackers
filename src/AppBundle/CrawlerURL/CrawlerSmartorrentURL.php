<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 25/12/14
 * Time: 22:21
 */

namespace AppBundle\CrawlerURL;

use AppBundle\AppBundle;
use AppBundle\Document\SmartorrentURL;
use Goutte\Client;


class CrawlerSmartorrentURL{

    private $urlDAO;
    private $baseURL = "http://smartorrent.com";

    public function __construct($urlDAO){
        $this->urlDAO = $urlDAO;
    }

    public function start(){
        $nbTotalPages = $this->_findNbPagesTotal();
        for($i = 1; $i <= $nbTotalPages; $i++){
            $url = $this->baseURL . '/torrents/' . $i . '/ordre/dd/';
            $this->_insertLinks($url);
        }

    }

    protected  function _findNbPagesTotal(){
        try {
            $client = new Client();
            $crawler = $client->request('GET', $this->baseURL . '/torrents');
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
