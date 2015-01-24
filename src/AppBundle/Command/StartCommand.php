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
        $tracker = $input->getArgument('tracker');
        //$controller = $this->getContainer()->get('main_controller');
        //$controller->dataAction($tracker, $input->getOption('categories'));
        $client = new Client();
        //Ensure that mongo-connector process is stopped
        exec("killall mongo-connector");
        //Delete all torrents in Solr for the right tracker
        $client->get('http://localhost:8983/solr/collection1/update?commit=true&stream.body=<delete><query>tracker:'. $tracker .'</query></delete>');
        //Delete oplogsstatus file and start mongo-connector to replicate data from mongo to Solr and stop mongo-connector
        exec("rm -f oplogstatus.txt ; mongo-connector -m localhost:27017 -t http://localhost:8983/solr -d /usr/local/lib/python2.7/dist-packages/mongo_connector/doc_managers/solr_doc_manager.py --oplog-ts oplogstatus.txt");

    }

} 