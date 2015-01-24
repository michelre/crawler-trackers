<?php
/**
 * Created by PhpStorm.
 * User: remimichel
 * Date: 23/01/15
 * Time: 21:55
 */

namespace AppBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use AppBundle\Controller\DefaultController;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StartCommand extends ContainerAwareCommand{

    protected function configure()
    {
        $this
            ->setName('crawler:data')
            ->setDescription('Crawler of trackers')
            ->addArgument(
                'tracker',
                InputArgument::REQUIRED,
                'Which tracker do you want to crawl?'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tracker = $input->getArgument('tracker');
        $controller = $this->getContainer()->get('main_controller');
        $controller->dataAction($tracker);
        //$result = $controller->dataAction($tracker);
        //$output->writeln($result);
    }

} 