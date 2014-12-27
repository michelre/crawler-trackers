<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 27/12/14
 * Time: 07:58
 */

namespace AppBundle\Doctrine;


class TorrentDAO {

    private $dm;

    public function __construct($dm){
        $this->dm = $dm;
    }

    public function findBySlug($slug){
        return $this->dm->getRepository('AppBundle:torrent')->findBy(array('slug' => $slug));
    }

    public function create($uri, $visited){

    }

    public function update($uri, $visited){

    }

    public function delete($uri){
        
    }

} 