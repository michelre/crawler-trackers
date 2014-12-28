<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 25/12/14
 * Time: 22:21
 */

namespace AppBundle\CrawlerData;

use AppBundle\AppBundle;
use AppBundle\Document\SmartorrentTorrent;
use Goutte\Client;


class CrawlerSmartorrentData {

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
        $nbTotalLinks = $this->urlDAO->countAll();
        $i = 0; $limit = 100;
        while($i < $nbTotalLinks){
            $urls = $this->urlDAO->findNotVisited($limit);
            foreach($urls as $url){
                $this->_extractData($url->getUri());
                $url->setVisited(true);
                $url->setUri($url->getUri());
                $url->setLastIndexationDate($url->getLastIndexationDate());
                $this->urlDAO->createOrUpdate($url);
            }
            $i += $limit;
            sleep(60);
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

        $clean = $str;//iconv('UTF-8', 'ASCII//TRANSLIT', $str);
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
    protected function _extractData($url){
        try {
            $client = new Client();
            $crawler = $client->request('GET', $url);
            $status_code = $client->getResponse()->getStatus();
            if ($status_code == 200) { // valid url and not reached depth limit yet
                if(!$crawler->filterXPath('//*[@id="contenuCentre"]/a[1]'))
                    return;
                else
                    $this->torrentDAO->createOrUpdate($this->_makeTorrentObject($crawler));
            }
        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $this->baseURL . '/torrents');
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $this->baseURL . '/torrents');
        }
    }

    protected function _makeTorrentObject($crawler){
        $index = 0;
        if(strcmp($crawler->filterXPath('//*[@id="contenuCentre"]/div[2]/table/tbody/tr[5]/td/strong')->text(), 'Lien externe:') == 0) $index = 1;
        $title = $crawler->filterXPath('//*[@id="contenuCentre"]/div[2]/table/tbody/tr[1]/td[2]/h1')->text();
        //$title = iconv('ISO-8859-1', 'UTF-8', $title);
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

        $torrent = new SmartorrentTorrent();
        $torrent->setTitle($title);
        $torrent->setSlug($slug);
        $torrent->setDescription('nc');
        $torrent->setDownloadLink($downloadLink);
        $torrent->setSize($size);
        $torrent->setSeeds($nbSeeders);
        $torrent->setLeechs($nbLeechers);
        $torrent->setTracker('smartorrent');
        $torrent->setCategory($category);

        return $torrent;
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