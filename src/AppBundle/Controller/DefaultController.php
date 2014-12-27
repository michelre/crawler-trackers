<?php

namespace AppBundle\Controller;

use AppBundle\CrawlerURL\CrawlerSmartorrentURL;
use AppBundle\Doctrine\UrlDAO;
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
     * @Route("crawl/v1/{tracker}/url", name="")
     */
    public function urlAction($tracker){
        $urlDAO = null;
        if(strcmp($tracker, "smartorrent") == 0){
            $dm = $this->get('doctrine_mongodb')->getManager();
            $urlDAO = new UrlDAO($dm, $dm->getRepository('AppBundle:SmartorrentURL'));
            $crawler = new CrawlerSmartorrentURL($urlDAO);
            $crawler->start();
        }
        return new Response("OK");
    }

    /**
     * @Route("crawl/v1/{tracker}/data", name="data")
     * @Method({"POST"})
     */
    public function dataAction($tracker){
        /*$dm = $this->get('doctrine_mongodb')->getManager();
        if(strcmp($tracker, "smartorrent") == 0){
            $crawler = new Smartorrent($dm);
            $crawler->start();
        }
        return new Response("OK " . $tracker);*/
    }
}