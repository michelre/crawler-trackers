<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 25/12/14
 * Time: 22:21
 */

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use Goutte\Client;
use AppBundle\Document\torrent;


class Smartorrent {

    private $base_url;
    private $urlDAO;
    private $torrentDAO;

    public function __construct($urlDAO, $torrentDAO){
        $this->base_url = "http://smartorrent.com";
        $this->urlDAO = $urlDAO;
        $this->torrentDAO = $torrentDAO;
    }

    /**
     * Init the crawling process
     * @param null $url
     */
    public function start($url = null){

    }

    protected function _linksToCrawl($limit){
        $links = array();
        $nbTorrents = $this->urlDAO->count();
        for($i = 0; $i < $nbTorrents; $i+=100){

        }
    }

    /**
     * Slugify a string
     * @param $str
     * @param array $replace
     * @param string $delimiter
     * @return mixed|string
     */
    protected function _slugify($str, $replace=array(), $delimiter='-') {
        if( !empty($replace) ) {
            $str = str_replace((array)$replace, ' ', $str);
        }

        $clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

        return $clean;
    }

    /**
     * Extract data from page
     * @param $crawler
     * @param $url
     */
    protected function _extractData($crawler, $url){
        if(!$crawler->filterXPath('//*[@id="contenuCentre"]/a[1]')){
            return;
        }else{
            $index = 0;
            if(strcmp($crawler->filterXPath('//*[@id="contenuCentre"]/div[2]/table/tbody/tr[5]/td/strong')->text(), 'Lien externe:') == 0) $index = 1;
            $title = $crawler->filterXPath('//*[@id="contenuCentre"]/div[2]/table/tbody/tr[1]/td[2]/h1')->text();
            $slug = $this->_slugify($title);
            $downloadLink = $crawler->filterXPath('//*[@id="contenuCentre"]/a[1]')->attr("href");
            $size = $crawler->filterXPath('//*[@id="contenuCentre"]/div[2]/table/tbody/tr['. (7+$index) .']/td/text()')->text();
            $seedersAndLeechersInfo = $crawler->filterXPath('//*[@id="contenuCentre"]/div[2]/table/tbody/tr['. (9+$index) .']/td/text()')->text();
            $nbSeeders = 'nc';
            $nbLeechers = 'nc';
            if($seedersAndLeechersInfo != ''){
                list($seedersText, $leechersText, $completedText) = explode('-', $seedersAndLeechersInfo);
                list($nbSeeders, $titleSeeder) = explode(' ', trim($seedersText));
                list($nbLeechers, $titleLeechers) = explode(' ', trim($leechersText));
            }
            $category = $crawler->filterXPath('//*[@id="contenuCentre"]/div[2]/table/tbody/tr[2]/td/a')->text();

            $torrent = new torrent();
            $torrent->setTitle($title);
            $torrent->setSlug($slug);
            $torrent->setDescription('nc');
            $torrent->setDownloadLink($downloadLink);
            $torrent->setSize($size);
            $torrent->setSeeds($nbSeeders);
            $torrent->setLeechs($nbLeechers);
            $torrent->setTracker('smartorrent');
            $torrent->setCategory($category);

            $this->dm->persist($torrent);
            $this->dm->flush();
            $this->logger->info("Torrent " . $torrent->getTitle() . " added");

        }
    }

    /**
     * check if the link leads to external site or not
     * @param string $url
     * @return boolean
     */
    public function checkIfExternal($url) {
        $base_url_trimmed = str_replace(array('http://', 'https://'), '', $this->base_url);

        if (preg_match("@http(s)?\://$base_url_trimmed@", $url)) { //base url is not the first portion of the url
            return false;
        } else {
            return true;
        }
    }

    /**
     * normalize link before visiting it
     * currently just remove url hash from the string
     * @param string $uri
     * @return string
     */
    protected function normalizeLink($uri) {
        $pattern = array("/é/", "/è/", "/ê/", "/ë/", "/ç/", "/à/", "/â/", "/î/", "/ï/", "/ù/", "/ô/", "/Â/", "/Ê/", "/É/", "/È/");
        $rep_pat = array("e", "e", "e", "e", "c", "a", "a", "i", "i", "u", "o", "A", "E", "E", "E");
        $uri = preg_replace('@#.*$@', '', $uri);
        $uri = preg_replace($pattern, $rep_pat, $uri);

        return $uri;
    }
} 