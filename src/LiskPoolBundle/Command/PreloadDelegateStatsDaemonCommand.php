<?php
namespace LiskPoolBundle\Command;

use Doctrine\Common\Collections\Criteria;
use LiskPhpBundle\Service\Lisk;
use LiskPoolBundle\Entity\Block;
use LiskPoolBundle\Entity\Delegate;
use LiskPoolBundle\Entity\Payout;
use LiskPoolBundle\Entity\Voter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class PreloadDelegateStatsDaemonCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('lisk:daemon:delegates')
            ->setDescription('Daemon that cached all delegates voting information to a cache.')
            ->setHelp('Daemon that cached all delegates voting information to a cache');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $delegateRewards = [
            "thepool" => [75, []],
            "liskpool_com_01" => [85, []],
            "luiz" => [25, ["Lisk Elite"]],
            "iii.element.iii" => [25, ["Lisk Elite"]],
            "liskpool.top" => [90, []],
            "forrest" => [25 , ["GDT"]],
            "tembo" => [15, ["GDT"]],
            "acheng" => [25, ["Lisk Elite"]],
            "badman0316" => [25, ["Lisk Elite"]],
            "spacetrucker" => [25, ["Lisk Elite"]],
            "leo" => [25, ["Lisk Elite"]],
            "liskjp" => [25, ["Lisk Elite"]],
            "panzer" => [25, ["Lisk Elite"]],
            "bigfisher" => [25, ["Lisk Elite"]],
            "bioly" => [25, ["GDT"]],
            "rooney" => [25, ["Lisk Elite"]],
            "ondin" => [25, ["GDT"]],
            "xujian" => [25, ["Lisk Elite"]],
            "will" => [25, ["Lisk Elite"]],
            "robinhood" => [100, ["SHW"]],
            "shinekami" => [80, []],
            "nerigal" => [25, ["GDT", "Lisk Elite Sponsored"]],
            "corsaro" => [15, ["GDT"]],
            "grajsondelegate" => [25, ["Lisk Elite"]],
            "eastwind_ja" => [25, ["Lisk Elite"]],
            "mrgr" => [25, ["Lisk Elite"]],
            "adrianhunter" => [25, ["Lisk Elite"]],
            "sgdias" => [30, ["GDT"]],
            "phinx" => [25, ["Lisk Elite"]],
            "luxiang7890" => [25, ["Lisk Elite"]],
            "liskit" => [20, ["GDT"]],
            "luukas" => [25, ["Lisk Elite"]],
            "crodam" => [25, ["Lisk Elite"]],
            "savetheworld" => [25, ["Lisk Elite"]],
            "seven" => [25, ["Lisk Elite"]],
            "liskroad" => [25, ["Lisk Elite"]],
            "carolina" => [25, ["Lisk Elite"]],
            "honeybee" => [25, ["Lisk Elite"]],
            "chamberlain" => [25, ["Lisk Elite"]],
            "someonesomeone" => [25, ["Lisk Elite"]],
            "hua" => [25, ["Lisk Elite"]],
            "mac" => [25, ["Lisk Elite"]],
            "augurproject" => [25, ["Lisk Elite"]],
            "forger_of_lisk" => [25, ["Lisk Elite"]],
            "lwyrup" => [25, ["Lisk Elite"]],
            "veriform" => [25, ["Lisk Elite"]],
            "goodtimes" => [25, ["Lisk Elite"]],
            "ntelo" => [20, ["GDT"]],
            "vi1son" => [40, ["GDT"]],
            "philhellmuth" => [30, ["GDT"]],
            "mrv" => [5, ["GDT"]],
            "crolisk" => [25, ["Lisk Elite"]],
            "zy1349" => [25, ["Lisk Elite"]],
            "hong" => [25, ["Lisk Elite"]],
            "bilibili" => [25, ["Lisk Elite"]],
            "loveforever" => [25, ["Lisk Elite"]],
            "cai" => [25, ["Lisk Elite"]],
            "yuandian" => [25, ["Lisk Elite"]],
            "jixie" => [25, ["Lisk Elite"]],
            "bigtom" => [25, ["Lisk Elite"]],
            "blackswan" => [25, ["Lisk Elite"]],
            "dakk" => [15, ["GDT"]],
            "jiandan" => [25, ["Lisk Elite"]],
            "dakini" => [25, ["Lisk Elite"]],
            "khitan" => [25, ["Lisk Elite"]],
            "menfei" => [25, ["Lisk Elite"]],
            "vipertkd" => [50, []],
            "kc" => [25, ["Lisk Elite"]],
            "china" => [25, ["Lisk Elite"]],
            "catstar" => [25, ["Lisk Elite"]],
            "threelittlepig" => [25, ["Lisk Elite"]],
            "kaystar" => [25, ["Lisk Elite"]],
            "elonhan" => [25, ["Lisk Elite"]],
            "stellardynamic" => [25, ["Lisk Elite Sponsored"]],
            "redsn0w" => [10, ["GDT"]],
            "slasheks" => [25, ["GDT", "GDT Sponsored"]],
            "bitbanksy" => [25, ["Lisk Elite Sponsored"]],
            "hagie" => [40, ["GDT"]],
            "vekexasia" => [25, ["GDT"]],
            "splatters" => [10, ["GDT"]],
            "vrlc92" => [50, []],
            "communitypool" => [80, []],
            "kushed.delegate" => [40, ["GDT"]],
            "samuray" => [50, []],
            "joel" => [25, ["GDT"]],
            "hoop" => [30, []],
            "devasive" => [25, []],
            "index" => [25, []],
            "vega" => [50, []],
            "phoenix1969" => [25, ["Lisk Elite Sponsored"]],
            "joo5ty" => [25, ["GDT", "GDT Sponsored"]],
            "gdtpool" => [100, ["GDT"]],
            "5an1ty" => [25, ["GDT", "GDT Sponsored"]],
            "atreides" => [40, []],
            "liskpro.com" => [0, ["SHW"]],
            "liberspirita" => [0, ["SHW"]],
            "techbytes" => [50, []],
            "bangomatic" => [50, []],
            "thrice.pi_prometheus" => [50, []],
            "bitseed" => [50, []],
            "fulig" => [20, []],
            "stoner19" => [40, []],
            "sonobit" => [60, []],
            "wannabe_rotebaron" => [40, []],
            "axente" => [50, []],
            "cad789" => [50, []],
            "djselery" => [25, []],
            "blink" => [90, []],
            "highrollerspool" => [70, []],
            "popcornbag" => [25, []],
            "alepop" => [40, []],
            "olejegcord" => [75, []],
            "anamix" => [60, []],
            "gregorst" => [50, []],
            "sapiens.io" => [60, []],
            "dutch_pool" => [75, ["Dutch Pool"]],
            "fnoufnou" => [75, ["Dutch Pool"]],
            "thamar" => [75, ["Dutch Pool"]],
            "kippers" => [75, ["Dutch Pool"]],
            "st3v3n" => [75, ["Dutch Pool"]],
            "hirish" => [70, []],
            "share99" => [99, []],
            "goforlisk" => [85, []],
            "cc001" => [0, ["GDT"]],
            "liskgate" => [0, ["GDT"]],
            "punkrock" => [0, ["GDT"]],
            "gr33ndrag0n" => [0, ["GDT"]],
            "grumlin" => [0, ["GDT"]],
            "goldeneye" => [0, ["GDT"]],
            "eclipsun" => [0, ["GDT"]],
            "4miners.net" => [0, ["GDT"]],
            "hmachado" => [0, ["GDT"]],
            "carbonara" => [25, ["Lisk Elite"]],
            "nimbus" => [50, []]
        ];

        $container = $this->getContainer();
        $lisk = $container->get("LiskPhpBundle\Service\Lisk");
        $em = $container->get('doctrine')->getManager();

        // Disable the SQL logger to prevent out of memory errors
        $em->getConnection()->getConfiguration()->setSQLLogger(null);

        $memcached = new \Memcached();
        $memcached->addServer($container->getParameter("lisk_pool.memcached.host"), $container->getParameter("lisk_pool.memcached.port"));

        while(1){
            foreach($delegateRewards as $delegateUsername => $delegateData){
                $delegate = $em->getRepository("LiskPoolBundle:Delegate")->findOneByUsername($delegateUsername);
                $delegateInfo = $lisk->getDelegateByUsername($delegateUsername);
                if(!$delegate){
                    $delegate = new Delegate();
                    $delegate->setUsername($delegateUsername);
                    $delegate->setAddress($delegateInfo["delegate"]["address"]);
                }
                $delegate->setRank($delegateInfo["delegate"]["rank"]);
                $delegate->setSharingPercentage($delegateData[0]);
                $delegate->setPools($delegateData[1]);
                $delegate->setVotedBalance($delegateInfo["delegate"]["vote"]);
                $em->persist($delegate);
                $em->flush();
            }

            // Clear the Doctrine EM to prevent memory issues
            $em->clear();
            $output->writeln("Processing done, sleeping for 1800 seconds...");
            sleep(1800);
        }
    }
}