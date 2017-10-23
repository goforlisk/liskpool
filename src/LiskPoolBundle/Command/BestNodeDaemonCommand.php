<?php
namespace LiskPoolBundle\Command;

use LiskPhpBundle\Service\Lisk;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class BestNodeDaemonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lisk:daemon:bestnode')
            ->setDescription('Daemon that determines the most up to date node from the configured nodes to Memcached every 10 seconds.')
            ->setHelp('Daemon that determines the most up to date node from the configured nodes to Memcached every 10 seconds.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $lisk = $container->get("LiskPhpBundle\Service\Lisk");
        $liskNodes = $container->getParameter("lisk_pool.forging.nodes");

        $memcached = new \Memcached();
        $memcached->addServer($container->getParameter("lisk_pool.memcached.host"), $container->getParameter("lisk_pool.memcached.port"));
        while(1){
            $bestNode = NULL;
            $bestNodeHeight = 0;
            $currentForgingNode = $memcached->get("lisk_pool.best_node");

            // There are no nodes configured, do nothing
            if(count($liskNodes) === 0){
                $output->writeln("No nodes configured! Not setting a best node.");
            }
            // There is only one node configured, no need to query the nodes for sync status, this one will always be the best node
            elseif(count($liskNodes) === 1){
                $lisk->setBaseUrl($liskNodes[0]);
                $synced = $lisk->getSyncStatus();
                if($synced["consensus"] === 100){
                    $output->writeln("Node " . $liskNodes[0] . " is the best synced node with a block height of " . $synced["height"]);
                    $bestNode = $liskNodes[0];
                    $bestNodeHeight = $synced["height"];
                } else {
                    $output->writeln("None of the nodes has full consensus! Not setting a new best node.");
                }
            } else {
                foreach($liskNodes as $liskNode){
                    $lisk->setBaseUrl($liskNode);
                    $synced = $lisk->getSyncStatus();
                    if($synced["height"] > $bestNodeHeight && $synced["consensus"] === 100){
                        $bestNode = $liskNode;
                        $bestNodeHeight = $synced["height"];
                    }
                }
                if(!$bestNode){
                    $output->writeln("None of the nodes has full consensus! Not setting a new best node.");
                }
            }

            if($bestNode === NULL){
                $output->writeln("None of the nodes has full consensus! Not setting a new best node.");
            } else {
                $memcached->set("lisk_pool.best_node", $bestNode);
                $output->writeln("Node " . $bestNode . " is the best synced node with a block height of " . $bestNodeHeight);

                // We have a new best node, disable forging on the old node and enable forging on this new node
                if($currentForgingNode !== $bestNode){
                    if(!empty($currentForgingNode)) {
                        $output->writeln("Disabling forging on previous best node " . $currentForgingNode);
                        $lisk->setBaseUrl($currentForgingNode);
                        $lisk->disableForging($container->getParameter("lisk_pool.forging.secret"));
                    }
                    $output->writeln("Enabling foring on new best node " . $bestNode);
                    $lisk->setBaseUrl($liskNodes[0]);
                    $lisk->enableForging($container->getParameter("lisk_pool.forging.secret"));
                }
            }

            $output->writeln("Sleep for 10 seconds...");
            sleep(10);
        }
    }
}