<?php

namespace AppBundle\Doctrine;


class TorrentDAO {

    private $dm;
    private $repositoryName;

    public function __construct($dm, $repositoryName){
        $this->dm = $dm;
        $this->repositoryName = $repositoryName;
    }

    public function findBySlug($slug){
        return $this->dm->getRepository('AppBundle:' . $this->repositoryName)->findBy(array('slug' => $slug));
    }


    public function createOrUpdate($torrent){
        if(!empty($torrents = $this->findBySlug($torrent->getSlug())))
            $this->update($torrent, $torrents[0]);
        else
            $this->dm->persist($torrent);
    }

    public function update($newTorrent, $torrent){
        $torrent->setSlug($newTorrent->getSlug());
        $torrent->setTitle($newTorrent->getTitle());
        $torrent->setDownloadLink($newTorrent->getDownloadLink());
        $torrent->setSize($newTorrent->getSize());
        $torrent->setSeeds($newTorrent->getSeeds());
        $torrent->setLeechs($newTorrent->getLeechs());
        $torrent->setUrl($newTorrent->getUrl());
        $torrent->setTracker($newTorrent->getTracker());
        $torrent->setCategory($newTorrent->getCategory());
    }

    public function flush(){
        $this->dm->flush();
    }

} 