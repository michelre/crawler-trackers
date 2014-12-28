<?php

namespace AppBundle\Doctrine;


class TorrentDAO {

    private $dm;

    public function __construct($dm){
        $this->dm = $dm;
    }

    public function findBySlug($slug){
        return $this->dm->getRepository('AppBundle:torrent')->findBy(array('slug' => $slug));
    }

    public function createOrUpdate($torrent){
        $this->dm->persist($torrent);
        $this->dm->flush();
    }

    public function update($uri, $visited){

    }

    public function delete($uri){
        
    }

} 