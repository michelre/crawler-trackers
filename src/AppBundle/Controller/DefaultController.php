<?php

namespace AppBundle\Controller;

use AppBundle\Crawler\SmartorrentCrawler;
use AppBundle\Crawler\CpasbienCrawler;
use AppBundle\Crawler\KickassCrawler;
use AppBundle\Doctrine\UrlDAO;
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
        if(strcmp($tracker, "smartorrent") == 0){
            $dm = $this->get('doctrine_mongodb')->getManager();
            $torrentDAO = new TorrentDAO($dm);
            $crawler = new SmartorrentCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "cpasbien") == 0){
            $dm = $this->get('doctrine_mongodb')->getManager();
            $torrentDAO = new TorrentDAO($dm);
            $crawler = new CpasbienCrawler($torrentDAO);
            $crawler->start();
        }
        if(strcmp($tracker, "kickass") == 0){
            $dm = $this->get('doctrine_mongodb')->getManager();
            $torrentDAO = new TorrentDAO($dm);
            $crawler = new KickassCrawler($torrentDAO);
            $crawler->start();
        }
        return new Response("OK");
    }
}
