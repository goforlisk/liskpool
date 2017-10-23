<?php

namespace LiskPoolBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use LiskPhpBundle\Service\Lisk;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StatisticsController extends Controller
{
    /**
     * @Route("/statistics", name="statistics")
     * @Method({"GET"})
     */
    public function getStatisticsAction(Lisk $lisk, Request $request)
    {
        $delegateUsername = $this->getParameter("lisk_pool.delegate_username");
        $delegateInfo = $lisk->getDelegateByUsername($delegateUsername);

        if(!isset($delegateInfo["success"]) || $delegateInfo["success"] !== TRUE){
            throw new NotFoundHttpException(var_export($delegateInfo, true));
        }

        return $this->render('LiskPoolBundle:Statistics:index.html.twig', [
            'delegate' => $delegateInfo["delegate"]
        ]);
    }

    /**
     * @Route("/statistics/detailed", name="statistics_detailed")
     * @Method({"GET"})
     */
    public function getStatisticsDetailedAction(Lisk $lisk, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $delegateUsername = $this->getParameter("lisk_pool.delegate_username");
        $delegateInfo = $lisk->getDelegateByUsername($delegateUsername);

        if(!isset($delegateInfo["success"]) || $delegateInfo["success"] !== TRUE){
            throw new NotFoundHttpException(var_export($delegateInfo, true));
        }

        $voters = $em->getRepository("LiskPoolBundle:Voter")->findBy([], ['balance' => 'DESC']);

        return $this->render('LiskPoolBundle:Statistics:detailed.html.twig', [
            'delegate' => $delegateInfo["delegate"],
            'voters' => $voters
        ]);
    }
}
