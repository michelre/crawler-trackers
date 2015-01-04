<?php

namespace AppBundle\Controller;

use AppBundle\Crawler\BtstorrentCrawler;
use AppBundle\Crawler\SmartorrentCrawler;
use AppBundle\Crawler\CpasbienCrawler;
use AppBundle\Crawler\ZetorrentsCrawler;
use AppBundle\Crawler\OmgCrawler;
use AppBundle\Doctrine\TorrentDAO;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;


class DefaultController extends Controller
{
    /**
     * @Route("/", name="homepage")
     */
    public function indexAction()
    {
        return new Response("Spec à venir");
    }

    /**
     * @Route("crawl/v1/{tracker}/data", name="data")
     * @Method({"POST"})
     */
    public function dataAction($tracker){
        $urlDAO = null; $torrentDAO = null;
        $logger = $this->get('logger');
        $dm = $this->get('doctrine_mongodb')->getManager();
        if(strcmp($tracker, "smartorrent") == 0){
            $torrentDAO = new TorrentDAO($dm, 'Smartorrent');
            $crawler = new SmartorrentCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "cpasbien") == 0){
            $torrentDAO = new TorrentDAO($dm, 'Cpasbien');
            $crawler = new CpasbienCrawler($torrentDAO, $logger);
            $crawler->start();
        }
        if(strcmp($tracker, "zetorrents") == 0){
            $torrentDAO = new TorrentDAO($dm, 'Zetorrents');
            $crawler = new ZetorrentsCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "btstorrent") == 0){
            $torrentDAO = new TorrentDAO($dm, 'Btstorrent');
            $crawler = new BtstorrentCrawler($torrentDAO, $logger);
            $crawler->start();
        }
        if(strcmp($tracker, "omg") == 0){
            $torrentDAO = new TorrentDAO($dm, 'Omg');
            $crawler = new OmgCrawler($torrentDAO, $logger);
            $crawler->start();
        }
        return new Response("OK");
    }
}
