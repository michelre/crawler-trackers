<?php

namespace AppBundle\Doctrine;


class UrlDAO {

    private $dm;
    private $repositoryName;

    public function __construct($dm, $repositoryName){
        $this->dm = $dm;
        $this->repositoryName = $repositoryName;
    }

    public function countAll(){
        return $this->dm->createQueryBuilder($this->repositoryName)
                 ->getQuery()
                 ->execute()
                 ->count();
    }

    public function findNotVisited($limit){
        return $this->dm->createQueryBuilder($this->repositoryName)
                        ->field('visited')->equals(false)
                        ->limit($limit)
                        ->getQuery()
                        ->execute();
    }

    public function findByURI($uri){
        return $this->dm->createQueryBuilder($this->repositoryName)
                        ->field('uri')->equals($uri)
                        ->getQuery()
                        ->getSingleResult();
    }

    public function createOrUpdate($url){
        $this->dm->persist($url);
        $this->dm->flush();
    }

    public function delete($uri){

    }

} 