<?php
namespace LiskPoolBundle\Command;

use Doctrine\Common\Collections\Criteria;
use LiskPhpBundle\Service\Lisk;
use LiskPoolBundle\Entity\Block;
use LiskPoolBundle\Entity\Delegate;
use LiskPoolBundle\Entity\DelegateHistory;
use LiskPoolBundle\Entity\Payout;
use LiskPoolBundle\Entity\Voter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class DelegateVotingHisoryDaemonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lisk:daemon:delegate:history')
            ->setDescription('Daemon that persists voting power history for all delegates to track changes in voting weight.')
            ->setHelp('Daemon that persists voting power history for all delegates to track changes in voting weight.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();
        $lisk = $container->get("LiskPhpBundle\Service\Lisk");
        $em = $container->get('doctrine')->getManager();

        // Disable the SQL logger to prevent out of memory errors
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $memcached = new \Memcached();
        $memcached->addServer($container->getParameter("lisk_pool.memcached.host"), $container->getParameter("lisk_pool.memcached.port"));

        while(1) {
            $offset = 0;
            $limit = 100; // Currently the maximum retrievable at once
            $delegates = [];

            $hasNoResults = FALSE;
            do {
                $data = $lisk->getDelegates($offset, $limit);
                if ($data["success"] === TRUE) {
                    $offset += 100;
                    if (count($data["delegates"]) > 0) {
                        $delegates = array_merge($delegates, $data["delegates"]);
                    } else {
                        $hasNoResults = TRUE;
                    }
                }
            } while ($hasNoResults === FALSE);

            foreach ($delegates as $delegate) {
                $delegateObj = $em->getRepository("LiskPoolBundle:Delegate")->findOneByAddress($delegate["address"]);
                $delegateInfo = $lisk->getDelegateByUsername($delegate["username"]);
                if ($delegateInfo["success"] === TRUE) {
                    if (!$delegateObj) {
                        $delegateObj = new Delegate();
                        $delegateObj->setAddress($delegate["address"]);
                    }
                    $delegateObj->setUsername($delegate["username"]);
                    $delegateObj->setVotedBalance($delegateInfo["delegate"]["vote"]);
                    $delegateObj->setRank($delegateInfo["delegate"]["rank"]);

                    $em->persist($delegateObj);
                    $em->flush();

                    // Create and persist a DelegateHistory object to keep track of voting power changes
                    $delegateHistory = new DelegateHistory();
                    $delegateHistory->setDelegate($delegateObj);
                    $delegateHistory->setVotedBalance($delegateInfo["delegate"]["vote"]);

                    $em->persist($delegateHistory);
                    $em->flush();
                } else {
                    echo "Delegate retrieval failed for delegate: " . $delegate["username"];
                    print_r($delegate);
                }
            }

            // Clear the Doctrine EM to prevent memory issues
            $em->clear();
            $output->writeln("Processing done, sleeping for 1800 seconds...");
            sleep(1800);
        }
    }
}