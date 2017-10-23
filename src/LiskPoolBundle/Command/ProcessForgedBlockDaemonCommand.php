<?php
namespace LiskPoolBundle\Command;

use LiskPhpBundle\Service\Lisk;
use LiskPoolBundle\Entity\Block;
use LiskPoolBundle\Entity\Voter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ProcessForgedBlockDaemonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lisk:daemon:processblocks')
            ->setDescription('Daemon that processes all forged blocks.')
            ->setHelp('Daemon that processes all forged blocks.');
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

        $publicKey = $container->getParameter("lisk_pool.forging.public_key");

        $poolFeePCt = $container->getParameter("lisk_pool.forging.fee_in_percentage");

        while(1){
            // Get the active forging node, this needs to be done every iteration since it may have changed by the BestNodeDaemon
            $forgingNode = $memcached->get("lisk_pool.best_node");
            $lisk->setBaseUrl($forgingNode);

            $output->writeln("Start processing round.");
            $blocks = $lisk->getBlocks($publicKey, 100);
            if($blocks["success"] === TRUE) {
                foreach ($blocks["blocks"] as $block) {
                    // Begin a DB transaction to prevent errors from corrupting our payouts
                    $em->getConnection()->beginTransaction();
                    $blockObject = $em->getRepository("LiskPoolBundle:Block")->findOneByBlockId($block["id"]);
                    if (!$blockObject) {
                        $output->writeln("Block with id " . $block["id"] . " does not exist, process it now.");

                        // Create the Block entity
                        $blockObject = new Block();
                        $blockObject->setBlockId($block["id"]);
                        $blockObject->setReward($block["totalForged"]);
                        $em->persist($blockObject);
                        $em->flush();

                        if($poolFeePCt > 0){
                            $poolFee = ($poolFeePCt / 100) * $block["totalForged"];
                        } else {
                            $poolFee = 0;
                        }

                        $shareableReward = $block["totalForged"] - $poolFee;
                        $shareableRewardChecker = 0;

                        $output->writeln("The reward for this block is " . $block["totalForged"]);
                        $output->writeln("The pool fee for this block is " . $poolFee);
                        $output->writeln("The shareable reward for this block is " . $shareableReward);

                        // Find out who has voted on this delegate
                        $voters = $lisk->getDelegateVoters($publicKey);
                        $votingPowerTotal = 0;
                        if ($voters["success"] === TRUE) {
                            // First loop al voters to determine the total voted power for this delegate
                            foreach ($voters["accounts"] as $voter) {
                                // Find out if this voter already exists in the database, otherwise add it
                                $voterObject = $em->getRepository("LiskPoolBundle:Voter")->findOneByAddress($voter["address"]);
                                if (!$voterObject) {
                                    $voterObject = new Voter();
                                    $voterObject->setAddress($voter["address"]);
                                    $em->persist($voterObject);
                                    $em->flush();
                                }

                                $votingPowerTotal += $voter["balance"];
                            }

                            $output->writeln("The total voted power for this delegate is: " . $votingPowerTotal);

                            // Now loop all voters again to determine their share in the block reward
                            foreach($voters["accounts"] as $voter){
                                $voterObject = $em->getRepository("LiskPoolBundle:Voter")->findOneByAddress($voter["address"]);
                                if($voterObject) {
                                    $voterRewardShare = $voter["balance"] / $votingPowerTotal;
                                    $voterReward = $voterRewardShare * $shareableReward;
                                    $shareableRewardChecker += $voterReward;
                                    //$output->writeln("Voter " . $voter["address"] . " with a balance of " . $voter["balance"] . " deserves " . $voterRewardShare . " of this block reward, which equals to " . $voterReward);
                                    $voterObject->setBalance($voterObject->getBalance() + $voterReward);
                                    $voterObject->setBalanceTotal($voterObject->getBalanceTotal() + $voterReward);
                                    $em->persist($voterObject);
                                    $em->flush($voterObject);
                                }
                            }

                            // We can't compare floats directly, so use a bcmath workaround
                            if(bcadd($shareableRewardChecker, $shareableReward, 1) != 2 * $shareableReward){
                                $output->writeln("Something went wrong! The shared rewards total (".$shareableRewardChecker.") does not add up to the amount of to-be-shared reward of the block (".$shareableReward.")!");
                                $em->getConnection()->rollback();
                                die("FATAL ERROR");
                            }
                        } else {
                            $output->writeln("An error occured retrieving the voters, stop processing and try again in the next iteration.");
                            break;
                        }
                    } else {
                        $output->writeln("Block with id " . $block["id"] . " already processed. Skipping.");
                    }

                    // Now flush the entire block processing transaction to the database
                    $em->getConnection()->commit();
                }

                // Clear the Doctrine EM to prevent memory issues
                $em->clear();
                gc_collect_cycles();
                //$output->writeln($this->getMemoryUsage());
                $output->writeln("Processing done, sleeping for 10 seconds...");
            } else {
                $output->writeln("An error occured retrieving the blocks, stop processing and try again in the next iteration.");
            }
            sleep(10);
        }
    }

    private function getMemoryUsage()
    {
        return sprintf('Memory usage (currently) %dKB/ (max) %dKB', round(memory_get_usage(true) / 1024), memory_get_peak_usage(true) / 1024);
    }
}