<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 12/03/15
 * Time: 08:51
 */

namespace CrawlerBundle\DependencyInjection;


class CrawlerExtension extends Extension{
    public function load(array $configs, ContainerBuilder $container){
        $loader = new XmlFileLoader(
            $container,
            new FileLocator(__DIR__.'/../Resources/config')
        );
        $loader->load('services.xml');
    }
} 