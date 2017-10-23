<?php

namespace LiskPoolBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use LiskPhpBundle\Service\Lisk;

class DefaultController extends Controller
{
    /**
     * @Route("/", name="home")
     * @Method({"GET"})
     */
    public function indexAction()
    {
        return $this->render('LiskPoolBundle:Default:index.html.twig', [

        ]);
    }

    public function getSyncStatusAction(Lisk $lisk){
        $memcached = new \Memcached();
        $memcached->addServer($this->getParameter("lisk_pool.memcached.host"), $this->getParameter("lisk_pool.memcached.port"));
        $lisk->setBaseUrl($memcached->get("lisk_pool.best_node"));

        $syncStatus = $lisk->getSyncStatus();
        if($syncStatus){
            $lastBlock = $lisk->getBlockByHeight($syncStatus["height"])["blocks"][0];
            $lastBlockHeight = $syncStatus["height"];
            $lastSyncTimestamp = 1464109200 + $lastBlock["timestamp"];
            $lastSync = new \DateTime();
            $lastSync->setTimestamp($lastSyncTimestamp);
        } else {
            $lastBlockHeight = "unknown";
            $lastSync = "unknown";
        }
        return $this->render('LiskPoolBundle:Default:includes/syncstatus.html.twig', [
            "blockHeight" => $lastBlockHeight,
            "lastSync" => $lastSync
        ]);
    }
}
