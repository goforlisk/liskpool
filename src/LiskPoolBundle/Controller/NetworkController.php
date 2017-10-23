<?php

namespace LiskPoolBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use LiskPhpBundle\Service\Lisk;

class NetworkController extends Controller
{
    /**
     * @Route("/network/statistics", name="network_statistics")
     * @Method({"GET"})
     */
    public function indexAction(Lisk $lisk)
    {
        $memcached = new \Memcached();
        $memcached->addServer($this->getParameter("lisk_pool.memcached.host"), $this->getParameter("lisk_pool.memcached.port"));

        $nodeStats = [];

        foreach($this->getParameter("lisk_pool.forging.nodes") as $liskNode){
            $lisk->setBaseUrl($liskNode);
            $parsedUrl = parse_url($liskNode);

            $node = $lisk->getSyncStatus();
            $node["name"] = $parsedUrl["host"];

            if($liskNode === $memcached->get("lisk_pool.best_node")){
                $node["best_node"] = TRUE;
            } else {
                $node["best_node"] = FALSE;
            }

            $nodeStats[] = $node;
        }

        return $this->render('LiskPoolBundle:Network:index.html.twig', [
            'nodeStats' => $nodeStats
        ]);
    }
}
