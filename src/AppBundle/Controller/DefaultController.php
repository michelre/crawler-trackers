<?php

namespace AppBundle\Controller;

use AppBundle\Crawler\Smartorrent;
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
     * @Route("/newdata/{tracker}", name="newdata")
     * @Method({"POST"})
     */
    public function smartorrentNewDataAction($tracker){
        $logger = $this->get('logger');
        $dm = $this->get('doctrine_mongodb')->getManager();
        if(strcmp($tracker, "smartorrent") == 0){
            $crawler = new Smartorrent($logger, $dm);
            $crawler->start();
        }
        return new Response("OK " . $tracker);
    }
}
