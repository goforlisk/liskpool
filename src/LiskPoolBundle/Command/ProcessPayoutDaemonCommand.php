<?php
namespace LiskPoolBundle\Command;

use Doctrine\Common\Collections\Criteria;
use LiskPhpBundle\Service\Lisk;
use LiskPoolBundle\Entity\Block;
use LiskPoolBundle\Entity\Payout;
use LiskPoolBundle\Entity\Voter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class ProcessPayoutDaemonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lisk:daemon:processpayments')
            ->setDescription('Daemon that processes all payments.')
            ->setHelp('Daemon that processes all payments.');
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

        $paymentThreshold = $container->getParameter("lisk_pool.forging.minimum_payout") * 100000000;

        $criteria = new Criteria();
        $criteria->where($criteria->expr()->gt('balance', $paymentThreshold));

        while(1){
            $payableAccounts = $em->getRepository("LiskPoolBundle:Voter")->matching($criteria);
            if($payableAccounts){
                foreach($payableAccounts as $payableAccount){
                    $output->writeln("Voter " . $payableAccount->getAddress() . " has a balance of " . ($payableAccount->getBalance() / 100000000) . "LSK, pay the user now.");

                    // Perform payment
                    $transaction = $lisk->sendTransaction($container->getParameter("lisk_pool.forging.secret"), ($payableAccount->getBalance() - 10000000), $payableAccount->getAddress(), $container->getParameter("lisk_pool.forging.second_secret"));
                    if($transaction["success"] === TRUE){
                        $payout = new Payout();
                        $payout->setAmount($payableAccount->getBalance() - 10000000);
                        $payout->setFee(10000000);
                        $payout->setTransactionId($transaction["transactionId"]);
                        $payout->setVoter($payableAccount);
                        $em->persist($payout);
                        $em->flush($payout);


                        $payableAccount->setBalance(0);
                        $em->persist($payableAccount);
                        $em->flush();
                    }
                }
            }

            $output->writeln("Processing done, sleeping for 3600 seconds...");
            sleep(3600);
        }
    }

    private function getMemoryUsage()
    {
        return sprintf('Memory usage (currently) %dKB/ (max) %dKB', round(memory_get_usage(true) / 1024), memory_get_peak_usage(true) / 1024);
    }
}