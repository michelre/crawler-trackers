<?php

namespace AppBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use GuzzleHttp\Client;

class StartCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this
            ->setName('crawler:data')
            ->setDescription('Crawler of trackers')
            ->addArgument('tracker', InputArgument::REQUIRED,'Which tracker do you want to crawl?')
            ->addOption('categories', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Which categories do you want to crawl');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
    	//Ensure that mongo-connector process is stopped to avoid replication during crawling
        exec("killall mongo-connector");
	    $tracker = $input->getArgument('tracker');
        $categories = $input->getOption('categories');
        $controller = $this->getContainer()->get('main_controller');
        $controller->dataAction($tracker, $categories);

        $client = new Client();
        //Delete all torrents in Solr for the right tracker
        if(empty($categories))
            $client->get('http://michelre:ddeeffg38c@localhost:8983/solr/collection1/update?commit=true&stream.body=<delete><query>tracker:'. $tracker .'</query></delete>');
        else
            $client->get('http://michelre:ddeeffg38c@localhost:8983/solr/collection1/update?commit=true&stream.body=<delete><query>tracker:'. $tracker .' AND category:(' . join(' OR ', $categories) .')</query></delete>');

        //Delete oplogsstatus file and start mongo-connector to replicate data from mongo to Solr
        exec("rm -f ~/oplogstatus.txt ; /usr/local/bin/mongo-connector -m localhost:27017 -t http://michelre:ddeeffg38c@localhost:8983/solr -d /usr/local/lib/python2.7/dist-packages/mongo_connector/doc_managers/solr_doc_manager.py --oplog-ts ~/oplogstatus.txt &");

    }

} 
