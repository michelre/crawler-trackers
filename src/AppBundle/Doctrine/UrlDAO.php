<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 27/12/14
 * Time: 07:58
 */

namespace AppBundle\Doctrine;


class UrlDAO {

    private $dm;
    private $repository;

    public function __construct($dm, $repository){
        $this->dm = $dm;
        $this->repository = $repository;
    }

    /*public function count($query = null){
        if(!$query)
            return $this-collection->getRepository('AppBundle:url')
                    ->count('{}');
        return null;
    }*/

    public function find($limit, $offset){
        return   $this->dm
                      ->find()
                      ->limit($limit)
                      ->skip($offset);
    }

    public function findByURI($uri){
        return $this->repository
                    ->findOneBy(array('uri' => $uri));
    }

    public function createOrUpdate($url){
        $this->dm->persist($url);
        $this->dm->flush();
    }

    public function delete($uri){

    }

} 