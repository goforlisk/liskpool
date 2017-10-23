<?php

namespace LiskPoolBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use LiskPhpBundle\Service\Lisk;

class SearchController extends Controller
{
    /**
     * @Route("/search", name="search")
     * @Method({"POST"})
     */
    public function searchAction(Lisk $lisk, Request $request)
    {
        $address = trim($request->request->get("address"));

        $voterObject = $this->getDoctrine()->getRepository("LiskPoolBundle:Voter")->findOneByAddress($address);

        $voters = $lisk->getDelegateVoters($this->getParameter("lisk_pool.forging.public_key"));
        $didVoteForUs = false;
        foreach($voters["accounts"] as $voter){
            if($address === $voter["address"]){
                $didVoteForUs = true;
                break;
            }
        }

        return $this->render('LiskPoolBundle:Search:index.html.twig', [
            'address' => $address,
            'voter' => $voterObject,
            'did_vote_for_us' => $didVoteForUs
        ]);
    }
}
