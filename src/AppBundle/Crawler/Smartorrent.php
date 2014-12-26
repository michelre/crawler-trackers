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
    private $site_links;
    private $logger;
    private $dm;

    public function __construct($logger, $dm){
        $this->base_url = "http://smartorrent.com";
        $this->site_links = array();
        $this->logger = $logger;
        $this->dm = $dm;
    }

    /**
     * Init the crawling process
     * @param null $url
     */
    public function start($url = null){
        $url = $this->base_url;
        $this->site_links[$url] = array(
            'visited' => false,
            'absolute_url' => $url
        );
        $this->_traverseSingle($url);
    }

    /**
     * crawling single url after checking the depth value
     * @param string $url_to_traverse
     */
    protected function _traverseSingle($url_to_traverse) {
        try {
            $client = new Client();
            $crawler = $client->request('GET', $url_to_traverse);

            $status_code = $client->getResponse()->getStatus();
            $this->site_links[$url_to_traverse]['status_code'] = $status_code;
            if ($status_code == 200) { // valid url and not reached depth limit yet
                $content_type = $client->getResponse()->getHeader('Content-Type');
                if (strpos($content_type, 'text/html') !== false) { //traverse children in case the response in HTML document
                    if(preg_match("@\/torrent\/Torrent@", $url_to_traverse))
                        $this->_extractData($crawler, $url_to_traverse);

                    $current_links = array();
                    if (@$this->site_links[$url_to_traverse]['external_link'] == false) { // for internal uris, get all links inside
                        $current_links = $this->_extractLinks($crawler, $url_to_traverse);
                    }

                    $this->site_links[$url_to_traverse]['visited'] = true; // mark current url as visited
                    $this->_traverseChildLinks($current_links);
                }
            }

        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $url_to_traverse);
            $this->site_links[$url_to_traverse]['status_code'] = '404';
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $url_to_traverse);
            $this->site_links[$url_to_traverse]['status_code'] = '404';
        }
    }

    protected function _traverseChildLinks($current_links){
        foreach($current_links as $uri => $info){
            if(!isset($this->site_links[$uri])){
                $this->site_links[$uri] = $info;
            }
            if(!empty($uri) && !$this->site_links[$uri]['visited']){
                $this->_traverseSingle($this->normalizeLink($current_links[$uri]['absolute_url']));
            }
        }
    }

    protected function _extractLinks($crawler, $url){
        $current_links = array();

        $crawler->filter('a')->each(function($node, $i) use (&$current_links, $url){
            $node_url = $node->attr('href');
            $hash = $this->_makeAbsoluteURL($this->normalizeLink($node_url));
            if(!isset($this->site_links[$hash]) && $this->checkIfCrawlable($node_url) && !isset($current_links[$hash])){
                if(preg_match("@\/torrents\/.*@", $node_url) || preg_match("@\/torrent\/Torrent.*\/\d*\/$@", $node_url)){
                    $current_links[$hash]["visited"] = false;
                    $current_links[$hash]["absolute_url"] = $hash;
                }
            }
            if(isset($current_links[$url])){ //Avoid cyclic loop
                $current_links[$url]['visited'] = true;
            }
        });
        return $current_links;
    }

    /**
     * Make url absolute
     * @param $url
     */
    protected function _makeAbsoluteURL($url){
        if(preg_match("@^http(s)?@", $url)){
            return $url;
        }
        return $this->base_url . $url;
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

    /**
     * checks the uri if can be crawled or not
     * in order to prevent links like "javascript:void(0)" or "#something" from being crawled again
     * @param string $uri
     * @return boolean
     */
    protected function checkIfCrawlable($uri) {
        if (empty($uri)) {
            return false;
        }

        $stop_links = array(//returned deadlinks
            '@^javascript\:void\(0\)$@',
            '@^#.*@',
        );

        foreach ($stop_links as $ptrn) {
            if (preg_match($ptrn, $uri)) {
                return false;
            }
        }

        return true;
    }

} 