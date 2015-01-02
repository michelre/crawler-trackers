<?php

namespace AppBundle\Crawler;

use AppBundle\AppBundle;
use AppBundle\Document\Kickass;
use GuzzleHttp\Event\CompleteEvent;
use GuzzleHttp\Client;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Pool;
use GuzzleHttp\Stream;



class KickassCrawler
{

    private $torrentDAO;
    private $baseURL = "https://kickass.so";
    private $poolSize = 100;
    private $dataFileName;

    public function __construct($torrentDAO)
    {
        $this->torrentDAO = $torrentDAO;
    }

    public function start()
    {
        $this->_downloadAndExtractDataFile();
        if (($handle = fopen($this->dataFileName, "r")) !== FALSE) {
            $row = 0;
            $requests = [];
            while (($data = fgetcsv($handle, 0, "|")) !== FALSE) {
                if($row < $this->poolSize){
                    array_push($requests, $this->_createRequest($data[3]));
                    $row += 1;
                }else{
                    $this->_extractTorrentsData($requests);
                    $this->torrentDAO->flush();
                    $requests = [];
                    $row = 0;
                }
            }
            fclose($handle);
        }
    }

    protected function _downloadAndExtractDataFile(){
        $client = new Client();
        //$client->get('https://kickass.so/dailydump.txt.gz',['save_to' => '/tmp/data.txt.gz']);
        //exec('gzip -d /tmp/data.txt.gz');
        $this->dataFileName = '/tmp/data.txt';
    }

    protected function _createRequest($url){
        $client = new \GuzzleHttp\Client();
        return $client->createRequest('GET', $url);
    }

    protected function _extractTorrentsData($requests)
    {
        $client = new Client();
        $torrents = [];
        Pool::send($client, $requests, array(
            'complete' => function (CompleteEvent $event) use(&$torrents) {
                    $body = $event->getResponse()->getBody()->getContents();
                    $crawler = new Crawler($body);
                    $torrent = $this->_createTorrentObject($crawler);
                    $this->torrentDAO->createOrUpdate($torrent);
                }
        ));
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

    protected function _createTorrentObject($node)
    {
        $title = trim($node->filter('.mainpart .novertmarg a span')->text());
        $slug = $this->_slugify($title);
        preg_match("/\(Size: (.*)\)/", $node->filter('.folderopen')->text(), $sizeText);
        $size = $sizeText[1];
        $seeds = $node->filter('.mainpart .seedBlock strong')->text();
        $leechs = $node->filter('.mainpart .leechBlock strong')->text();
        $url = $this->baseURL . $node->filter('.mainpart .novertmarg a')->attr('href');
        preg_match("/t(\d.*).html$/", $url, $idCategory);
        $category = $node->filter('.mainpart #cat_'.$idCategory[1].' strong a')->text();
        $downloadLink = $node->filter('.mainpart a[rel="nofollow"].siteButton')->attr('href');
        $torrent = new Kickass();
        $torrent->setSlug($slug);
        $torrent->setTitle($title);
        $torrent->setCategory($category);
        $torrent->setSize($size);
        $torrent->setSeeds($seeds);
        $torrent->setLeechs($leechs);
        $torrent->setUrl($url);
        $torrent->setDownloadLink($downloadLink);
        $torrent->setTracker("kickass");
        return $torrent;

    }
}