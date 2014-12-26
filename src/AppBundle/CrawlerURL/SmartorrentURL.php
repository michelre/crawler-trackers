<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 25/12/14
 * Time: 22:21
 */

namespace AppBundle\CrawlerURL;

use AppBundle\AppBundle;
use AppBundle\Document\url;
use Goutte\Client;


class SmartorrentURL {

    private $base_url;
    private $dm;

    public function __construct($dm){
        $this->base_url = "http://smartorrent.com";
        $this->site_links = array();
        $this->dm = $dm;
    }

    /**
     * Init the crawling process
     * @param null $url
     */
    public function start($url = null){
        $url = $this->base_url;
        $this->_addURL($url, false);
        $this->_traverseSingle($url);
    }

    protected function _addURL($uri, $visited){
        $url = new url();
        $url->setUri($uri);
        $url->setVisited($visited);
        $url->setLastIndexationDate(date("Y-m-d"));
        $this->dm->persist($url);
        $this->dm->flush();
    }

    protected function _setURL($uri, $visited){
        $url = $this->_findURL($uri);
        if($url !== null){
            $url->setVisited($visited);
            $this->dm->persist($url);
            $this->dm->flush();
        }
    }

    protected function _findURL($uri){
        return $this->dm->getRepository('AppBundle:url')->findOneBy(array('uri' => $uri));
    }

    /**
     * crawling single url
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
                if (strpos($content_type, 'text/html') !== false) {

                    $current_links = array();
                    if (preg_match("@\/torrents\/@", $url_to_traverse) || strcmp($this->base_url, $url_to_traverse) == 0) { // for internal uris, get all links inside
                        $current_links = $this->_extractLinks($crawler, $url_to_traverse);
                    }

                    $this->_setURL($url_to_traverse, true); // mark current url as visited
                    $this->_traverseChildLinks($current_links);
                }
            }

        } catch (Guzzle\Http\Exception\CurlException $ex) {
            error_log("CURL exception: " . $url_to_traverse);
        } catch (Exception $ex) {
            error_log("error retrieving data from link: " . $url_to_traverse);
        }
    }

    protected function _traverseChildLinks($current_links){
        foreach($current_links as $uri => $info){
            if($this->_findURL($uri) == null){
                $this->_addURL($uri, false);
            }

            if($this->_findURL($uri) != null && !$this->_findURL($uri)->getVisited()){
                $this->_traverseSingle($this->normalizeLink($uri));
            }

        }
    }

    protected function _extractLinks($crawler, $url){
        $current_links = array();

        $crawler->filter('a')->each(function($node, $i) use (&$current_links, $url){
            $node_url = $node->attr('href');
            $hash = $this->_makeAbsoluteURL($this->normalizeLink($node_url));

            if($this->_findURL($hash) == null && $this->checkIfCrawlable($node_url) && !isset($current_links[$hash])){
                if(preg_match("@\/torrents\/.*@", $node_url) || preg_match("@\/torrent\/Torrent.*\/\d*\/$@", $node_url)){
                    $current_links[$hash]["visited"] = false;
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