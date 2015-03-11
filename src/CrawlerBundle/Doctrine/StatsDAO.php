<?php

namespace AppBundle\Doctrine;


class StatsDAO {

    private $dm;

    public function __construct($dm){
        $this->dm = $dm;
    }

    public function findByTracker($tracker){
        return $this->dm->getRepository('AppBundle:stats')->findBy(array('tracker' => $tracker));
    }


    public function createOrUpdate($stats){
        $statsFound = $this->findByTracker($stats->getTracker());
        if(!empty($statsFound))
            $this->update($stats, $statsFound[0]);
        else
            $this->dm->persist($stats);
        $this->dm->flush();
    }

    public function update($newStats, $stats){
        $stats->setNb($newStats->getNb());
        $stats->setLastIndexationDate($newStats->getLastIndexationDate());
    }

} 