<?php

namespace LiskPoolBundle\Controller;

use Doctrine\Common\Collections\Criteria;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use LiskPhpBundle\Service\Lisk;

class RewardCalculatorController extends Controller
{
    private $blockRewardPerMonth = ((86400 / 10 / 101) * 5 * 365) / 12;

    /**
     * @Route("/rewards", name="rewards")
     * @Route("/rewards/calculator", name="rewards_calculator")
     * @Method({"GET", "POST"})
     */
    public function indexAction(Lisk $lisk, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $totalRewards = $optimizedRewards = $idealRewards = $idealRewardsCurrentlyForging = 0;
        $forgeStats = [];
        $votedDelegateUsernames = [];
        $idealDelegateUsernames = [];
        $gdtpoolIsForging = false;
        $liskBalance = 0;

        $finalOptimizedForgeStats = [];
        $idealForgeStats = [];


        // Generate a (un)vote list for the ideal situation
        $idealVotes = [
            "vote" => [],
            "unvote" => [],
            "rounds" => []
        ];

        $hasValidAddress = false;
        $address = trim($request->request->get("address"));
        $excludedPools = $request->request->get("exclude_pools");
        if(!empty($address)){
            $ourBalance = $lisk->getAccountBalance($address);
            if($ourBalance["success"] === TRUE) {
                $hasValidAddress = true;
                $liskBalance = $ourBalance["balance"];

                // Calculate the current situation
                $delegatesInfo = $lisk->getVotesByAddress($address);
                if ($delegatesInfo["success"] === TRUE) {
                    $qb = $em->createQueryBuilder();
                    $qb->select('d.username, d.pools, d.sharingPercentage, d.rank, d.votedBalance')
                        ->from("LiskPoolBundle:Delegate", 'd')
                        ->where('d.pools LIKE :pools');

                    $eliteMembers = $qb->setParameter('pools', '%"Lisk Elite"%')->getQuery()->getResult();
                    $eliteSupportedMembers = $qb->setParameter('pools', '%"Lisk Elite Sponsored"%')->getQuery()->getResult();
                    $GDTMembers = $qb->setParameter('pools', '%"GDT"%')->getQuery()->getResult();
                    $GDTSupportedMembers = $qb->setParameter('pools', '%"GDT Sponsored"%')->getQuery()->getResult();
                    $dutchPoolMembers = $qb->setParameter('pools', '%"Dutch Pool"%')->getQuery()->getResult();
                    $SHWMembers = $qb->setParameter('pools', '%"SHW"%')->getQuery()->getResult();

                    $eliteMembersArray = array_map(function($member) {
                        return $member["username"];
                    }, $eliteMembers);

                    $eliteSupportedMembersArray = array_map(function($member) {
                        return $member["username"];
                    }, $eliteSupportedMembers);

                    $GDTMembersArray = array_map(function($member) {
                        return $member["username"];
                    }, $GDTMembers);

                    $GDTSupportedMembersArray = array_map(function($member) {
                        return $member["username"];
                    }, $GDTSupportedMembers);

                    $dutchPoolMembersArray = array_map(function($member) {
                        return $member["username"];
                    }, $dutchPoolMembers);

                    $SHWMembersArray = array_map(function($member) {
                        return $member["username"];
                    }, $SHWMembers);

                    /* Mock a Elite vote
                    $delegatesInfo["delegates"][] = [
                        "username" => "luiz",
                        "address" => "7785089688506705621L",
                        "publicKey" => "c4d96fbfe80102f01579945fe0c5fe2a1874a7ffeca6bacef39140f9358e3db6",
                        "vote" => "3481530034218061",
                        "producedblocks" => 28937,
                        "missedblocks" => 249,
                        "rate" => 2,
                        "rank" => 2,
                        "approval" => 30.31,
                        "productivity" => 99.15
                    ];*/
                    foreach ($delegatesInfo["delegates"] as $votedDelegate) {
                        $votedDelegateUsernames[] = $votedDelegate["username"];

                        $delegate = $em->getRepository("LiskPoolBundle:Delegate")->findOneByUsername($votedDelegate["username"]);
                        if(!$delegate){
                            $forgeStats[] = [
                                "delegate" => $votedDelegate["username"],
                                "pools" => ["Unknown"],
                                "rank" => "Unknown",
                                "share" => 0,
                                "share_real" => 0,
                                "rewards" => 0,
                                "rewards_real" => 0,
                                "missing_votes" => []
                            ];
                        } else {
                            $votingPower = $delegate->getVotedBalance();
                            $ourShare = $liskBalance / $votingPower;
                            $ourRewards = $ourShare * $this->blockRewardPerMonth * ($delegate->getSharingPercentage() / 100);

                            // This user has voted an Elite member, remove it from the list of members that are stil required to vote
                            $isEliteKey = array_search($delegate->getUsername(), $eliteMembersArray);
                            if($isEliteKey !== FALSE){
                                unset($eliteMembersArray[$isEliteKey]);
                            }

                            // This user has voted an Elite sponsored member, remove it from the list of members that are stil required to vote
                            $isEliteSupportedKey = array_search($delegate->getUsername(), $eliteSupportedMembersArray);
                            if($isEliteSupportedKey !== FALSE){
                                array_splice($eliteSupportedMembersArray, $isEliteSupportedKey, 1);
                            }

                            // This user has voted a GDT member, remove it from the list of members that are stil required to vote
                            $isGDTKey = array_search($delegate->getUsername(), $GDTMembersArray);
                            if($isGDTKey !== FALSE){
                                array_splice($GDTMembersArray, $isGDTKey, 1);
                            }

                            // This user has voted a GDT sponsored member, remove it from the list of members that are stil required to vote
                            $isGDTSupportedKey = array_search($delegate->getUsername(), $GDTSupportedMembersArray);
                            if($isGDTSupportedKey !== FALSE){
                                array_splice($GDTSupportedMembersArray, $isGDTSupportedKey, 1);
                            }

                            // This user has voted a SHW member, remove it from the list of members that are stil required to vote
                            $isSHWKey = array_search($delegate->getUsername(), $SHWMembersArray);
                            if($isSHWKey !== FALSE){
                                array_splice($SHWMembersArray, $isSHWKey, 1);
                            }

                            // This user has voted a Dutch Pool member, remove it from the list of members that are stil required to vote
                            $isDutchPoolKey = array_search($delegate->getUsername(), $dutchPoolMembersArray);
                            if($isDutchPoolKey !== FALSE){
                                array_splice($dutchPoolMembersArray, $isDutchPoolKey, 1);
                            }

                            if($delegate->getUsername() === "gdtpool" && $delegate->getRank() <= 101){
                                $gdtpoolIsForging = TRUE;
                            }

                            $forgeStats[] = [
                                "delegate" => $votedDelegate["username"],
                                "pools" => $delegate->getPools(),
                                "rank" => $delegate->getRank(),
                                "share" => $ourShare,
                                "share_real" => $ourShare,
                                "rewards" => $delegate->getRank() <= 101 ? $ourRewards : 0,
                                "rewards_real" => $delegate->getRank() <= 101 ? $ourRewards : 0,
                                "missing_votes" => []
                            ];
                        }
                    }

                    // Re-iterate the forgeStats to correct for pool rules
                    foreach($forgeStats as &$forgeStat){
                        // This is an Elite member, set the reward to 0 if not all Elite members are voted
                        if(in_array("Lisk Elite", $forgeStat["pools"]) && count($eliteMembersArray) > 0){
                            $forgeStat["share_real"] = 0;
                            $forgeStat["rewards_real"] = 0;
                            $forgeStat["missing_votes"] = $eliteMembersArray;
                        }

                        // This is an Elite Supported Member that shares, set the reward to 0 if not all Elite members are voted
                        if(in_array("Lisk Elite Sponsored", $forgeStat["pools"]) && count($eliteMembersArray) > 0){
                            $forgeStat["share_real"] = 0;
                            $forgeStat["rewards_real"] = 0;
                            $forgeStat["missing_votes"] = $eliteMembersArray;
                        }

                        // This is the robinhood pool, set the reward to 75 if not all SHW members are voted, if all are voted, set the reward to 120
                        if($forgeStat["delegate"] === "robinhood"){
                            if(count($SHWMembersArray) > 0) {
                                $forgeStat["share_real"] = 0.75 * $forgeStat["share_real"];
                                $forgeStat["rewards_real"] = 0.75 * $forgeStat["rewards_real"];
                                $forgeStat["missing_votes"] = $SHWMembersArray;
                            } else {
                                $forgeStat["share_real"] = 1.20 * $forgeStat["share_real"];
                                $forgeStat["rewards_real"] = 1.20 * $forgeStat["rewards_real"];
                            }
                        }

                        // This is the gdtpool pool, process its rules
                        if(in_array("GDT", $forgeStat["pools"])){
                            // If not all members are voted - no rewards
                            if(count($GDTMembersArray) > 0) {
                                $forgeStat["share_real"] = 0;
                                $forgeStat["rewards_real"] = 0;
                                $forgeStat["missing_votes"] = $GDTMembersArray;
                            }
                            // If not all supported members are voted (= silver level) and gdtpool is not forging - no rewards
                            elseif(count($GDTSupportedMembersArray) > 0 && !$gdtpoolIsForging) {
                                $forgeStat["share_real"] = 0;
                                $forgeStat["rewards_real"] = 0;
                            }
                            // Otherwise a "bonus" is gained, but it is not clear what that is exactly, so do nothing with this info
                            else {
                                //???
                            }
                        }

                        $totalRewards += $forgeStat["rewards_real"];
                    }
                }

                // Calculate optimized scenario by adding up rewards from all forging delegates
                $eliteMembersArray = array_map(function($member) {
                    return $member["username"];
                }, $eliteMembers);

                $eliteSupportedMembersArray = array_map(function($member) {
                    return $member["username"];
                }, $eliteSupportedMembers);

                $GDTMembersArray = array_map(function($member) {
                    return $member["username"];
                }, $GDTMembers);

                $GDTSupportedMembersArray = array_map(function($member) {
                    return $member["username"];
                }, $GDTSupportedMembers);

                $dutchPoolMembersArray = array_map(function($member) {
                    return $member["username"];
                }, $dutchPoolMembers);

                $SHWMembersArray = array_map(function($member) {
                    return $member["username"];
                }, $SHWMembers);

                $delegates = $lisk->getDelegates();
                $optimizedForgeStats = [];
                foreach($delegates["delegates"] as $delegate){
                    $delegateObject = $em->getRepository("LiskPoolBundle:Delegate")->findOneByUsername($delegate["username"]);
                    if(!$delegateObject){
                        $optimizedForgeStats[] = [
                            "delegate" => $delegate["username"],
                            "pools" => ["Unknown"],
                            "rank" => $delegate["rank"],
                            "share" => 0,
                            "rewards" => 0,
                        ];
                    } else {
                        $votingPower = $delegateObject->getVotedBalance();
                        $ourShare = $liskBalance / $votingPower;
                        $ourRewards = $ourShare * $this->blockRewardPerMonth * ($delegateObject->getSharingPercentage() / 100);
                        $optimizedForgeStats[] = [
                            "delegate" => $delegate["username"],
                            "pools" => $delegateObject->getPools(),
                            "rank" => $delegate["rank"],
                            "share" => $ourShare,
                            "rewards" => $ourRewards,
                        ];
                    }
                }

                // Sort the array by rewards from high -> low
                usort($optimizedForgeStats, function($a, $b){
                    return $a["rewards"] > $b["rewards"];
                });

                // Now we have to correct for pool rules, for now we assume that when hitting a pool member in the list it is likely advisable to vote them all
                // This calculation is obviously not fail-safe, but the pools at this point are large enough with many delegates to make this assumption nearly correct
                $poolsProcessed = [];
                foreach($optimizedForgeStats as $optimizedForgeStat){
                    if(count($finalOptimizedForgeStats) === 101){
                        break;
                    }

                    // We have hit a large pool, if it is Elite or GDT we have to vote them all to get to optimized results
                    if(!empty($optimizedForgeStat["pools"]) && (in_array("Lisk Elite", $optimizedForgeStat["pools"]) || in_array("GDT", $optimizedForgeStat["pools"]))){
                        if(in_array("Lisk Elite", $optimizedForgeStat["pools"]) && in_array("Lisk Elite", $poolsProcessed)){
                            continue;
                        }
                        if(in_array("GDT", $optimizedForgeStat["pools"]) && in_array("GDT", $poolsProcessed)){
                            continue;
                        }

                        if(in_array("GDT", $optimizedForgeStat["pools"])){
                            // If there is not enough slots to vote for GDT members, unvote as many independents as needed
                            if(count($finalOptimizedForgeStats) + count($GDTMembersArray) > 101){
                                array_slice($finalOptimizedForgeStats, -(count($finalOptimizedForgeStats) + count($GDTMembersArray) - 101));
                            }
                            $finalOptimizedForgeStats[] = $optimizedForgeStat;

                            foreach($GDTMembers as $GDTMember){
                                if($GDTMember["username"] === $optimizedForgeStat["delegate"]){
                                    continue;
                                }

                                $votingPower = $GDTMember["votedBalance"];
                                $ourShare = $liskBalance / $votingPower;
                                $ourRewards = $ourShare * $this->blockRewardPerMonth * ($GDTMember["sharingPercentage"] / 100);

                                $finalOptimizedForgeStats[] = [
                                    "delegate" => $GDTMember["username"],
                                    "pools" => $GDTMember["pools"],
                                    "rank" => $GDTMember["rank"],
                                    "share" => $ourShare,
                                    "rewards" => $ourRewards,
                                ];
                            }
                            $poolsProcessed[] = "GDT";
                        }

                        if(in_array("Lisk Elite", $optimizedForgeStat["pools"])){
                            // If there is not enough slots to vote for Elite members, unvote as many independents as needed
                            if(count($finalOptimizedForgeStats) + count($eliteMembersArray) > 101){
                                array_slice($finalOptimizedForgeStats, -(count($finalOptimizedForgeStats) + count($eliteMembersArray) - 101));
                            }
                            $finalOptimizedForgeStats[] = $optimizedForgeStat;

                            foreach($eliteMembers as $eliteMember){
                                if($eliteMember["username"] === $optimizedForgeStat["delegate"]){
                                    continue;
                                }
                                $votingPower = $eliteMember["votedBalance"];
                                $ourShare = $liskBalance / $votingPower;
                                $ourRewards = $ourShare * $this->blockRewardPerMonth * ($eliteMember["sharingPercentage"] / 100);

                                $finalOptimizedForgeStats[] = [
                                    "delegate" => $eliteMember["username"],
                                    "pools" => $eliteMember["pools"],
                                    "rank" => $eliteMember["rank"],
                                    "share" => $ourShare,
                                    "rewards" => $ourRewards,
                                ];
                            }
                            $poolsProcessed[] = "Lisk Elite";
                        }
                    } else {
                        $finalOptimizedForgeStats[] = $optimizedForgeStat;
                    }
                }

                // Sort the array by rewards from high -> low
                usort($finalOptimizedForgeStats, function($a, $b){
                    return $a["rewards"] > $b["rewards"];
                });

                // Calculate the ideal rewards
                // If a delegate is not ranked 101 or better, assume a 101 ranked delegate for the voting share
                $delegateRank101 = $em->getRepository("LiskPoolBundle:Delegate")->findOneByRank(101);
                $voteWeight101 = $delegateRank101->getVotedBalance();

                $delegates = $em->getRepository("LiskPoolBundle:Delegate")->findBy([], ["sharingPercentage" => "DESC"]);
                $poolsProcessed = [];
                foreach($delegates as $delegate){
                    if(count($idealForgeStats) === 101){
                        break;
                    }

                    if((in_array("Lisk Elite", $delegate->getPools()) || in_array("Lisk Elite Sponsored", $delegate->getPools())) && in_array("Lisk Elite", $excludedPools)){
                        continue;
                    }
                    if((in_array("GDT", $delegate->getPools()) || in_array("GDT Sponsored", $delegate->getPools())) && in_array("GDT", $excludedPools)){
                        continue;
                    }
                    if(in_array("SHW", $delegate->getPools()) && in_array("SHW", $excludedPools)){
                        continue;
                    }
                    if(in_array("Dutch Pool", $delegate->getPools()) && in_array("Dutch Pool", $excludedPools)){
                        continue;
                    }

                    // We have hit a large pool, if it is Elite or GDT we have to vote them all to get to optimized results
                    if(!empty($delegate->getPools()) && (in_array("Lisk Elite", $delegate->getPools()) || in_array("GDT", $delegate->getPools()))){
                        if(in_array("Lisk Elite", $delegate->getPools()) && in_array("Lisk Elite", $poolsProcessed)){
                            continue;
                        }
                        if(in_array("GDT", $delegate->getPools()) && in_array("GDT", $poolsProcessed)){
                            continue;
                        }

                        if(in_array("GDT", $delegate->getPools())){
                            // If there is not enough slots to vote for GDT members, unvote as many independents as needed
                            $searchOffset = -1;
                            while(count($idealForgeStats) + count($GDTMembersArray) > 101){
                                $lastKey = count($idealForgeStats) - $searchOffset;
                                $lastItem = $idealForgeStats[$lastKey];
                                if(!in_array("GDT", $lastItem["pools"]) && !in_array("Lisk Elite", $lastItem["pools"])){
                                    unset($idealForgeStats[$lastKey]);
                                    unset($idealDelegateUsernames[$lastKey]);
                                    $searchOffset = -1;
                                } else {
                                    $searchOffset -= 1;
                                }
                            }

                            if($delegate->getRank() > 101){
                                $votingPower = $voteWeight101;
                            } else {
                                $votingPower = $delegate->getVotedBalance();
                            }
                            $ourShare = $liskBalance / $votingPower;
                            $ourRewards = $ourShare * $this->blockRewardPerMonth * ($delegate->getSharingPercentage() / 100);

                            $idealDelegateUsernames[] = $delegate->getUsername();

                            $idealForgeStats[] = [
                                "delegate" => $delegate->getUsername(),
                                "pools" => $delegate->getPools(),
                                "rank" => $delegate->getRank(),
                                "share" => $ourShare,
                                "rewards" => $ourRewards,
                            ];
                            $idealRewards += $ourRewards;
                            if($delegate->getRank() <= 101){
                                $idealRewardsCurrentlyForging += $ourRewards;
                            }

                            foreach($GDTMembers as $GDTMember){
                                if($GDTMember["username"] === $delegate->getUsername()){
                                    continue;
                                }

                                if($GDTMember["rank"] > 101){
                                    $votingPower = $voteWeight101;
                                } else {
                                    $votingPower = $GDTMember["votedBalance"];
                                }

                                $ourShare = $liskBalance / $votingPower;
                                $ourRewards = $ourShare * $this->blockRewardPerMonth * ($GDTMember["sharingPercentage"] / 100);

                                $idealForgeStats[] = [
                                    "delegate" => $GDTMember["username"],
                                    "pools" => $GDTMember["pools"],
                                    "rank" => $GDTMember["rank"],
                                    "share" => $ourShare,
                                    "rewards" => $ourRewards,
                                ];
                                $idealRewards += $ourRewards;

                                $idealDelegateUsernames[] = $GDTMember["username"];

                                if($GDTMember["rank"] <= 101){
                                    $idealRewardsCurrentlyForging += $ourRewards;
                                }
                            }
                            $poolsProcessed[] = "GDT";
                        }

                        if(in_array("Lisk Elite", $delegate->getPools())){
                            // If there is not enough slots to vote for Elite members, unvote as many independents as needed
                            $searchOffset = -1;
                            while(count($idealForgeStats) + count($eliteMembersArray) > 101){
                                $lastKey = count($idealForgeStats) + $searchOffset;
                                $lastItem = $idealForgeStats[$lastKey];
                                if(!in_array("GDT", $lastItem["pools"]) && !in_array("Lisk Elite", $lastItem["pools"])){
                                    unset($idealForgeStats[$lastKey]);
                                    unset($idealDelegateUsernames[$lastKey]);
                                    $searchOffset = -1;
                                } else {
                                    $searchOffset -= 1;
                                }
                            }

                            if($delegate->getRank() > 101){
                                $votingPower = $voteWeight101;
                            } else {
                                $votingPower = $delegate->getVotedBalance();
                            }
                            $ourShare = $liskBalance / $votingPower;
                            $ourRewards = $ourShare * $this->blockRewardPerMonth * ($delegate->getSharingPercentage() / 100);

                            $idealForgeStats[] = [
                                "delegate" => $delegate->getUsername(),
                                "pools" => $delegate->getPools(),
                                "rank" => $delegate->getRank(),
                                "share" => $ourShare,
                                "rewards" => $ourRewards,
                            ];
                            $idealRewards += $ourRewards;

                            $idealDelegateUsernames[] = $delegate->getUsername();

                            if($delegate->getRank() <= 101){
                                $idealRewardsCurrentlyForging += $ourRewards;
                            }
                            foreach($eliteMembers as $eliteMember){
                                if($eliteMember["username"] === $delegate->getUsername()){
                                    continue;
                                }

                                if($eliteMember["rank"] > 101){
                                    $votingPower = $voteWeight101;
                                } else {
                                    $votingPower = $eliteMember["votedBalance"];
                                }
                                $ourShare = $liskBalance / $votingPower;
                                $ourRewards = $ourShare * $this->blockRewardPerMonth * ($eliteMember["sharingPercentage"] / 100);

                                $idealForgeStats[] = [
                                    "delegate" => $eliteMember["username"],
                                    "pools" => $eliteMember["pools"],
                                    "rank" => $eliteMember["rank"],
                                    "share" => $ourShare,
                                    "rewards" => $ourRewards,
                                ];
                                $idealRewards += $ourRewards;

                                $idealDelegateUsernames[] = $eliteMember["username"];

                                if($GDTMember["rank"] <= 101){
                                    $idealRewardsCurrentlyForging += $ourRewards;
                                }
                            }
                            $poolsProcessed[] = "Lisk Elite";
                        }
                    } else {
                        if($delegate->getRank() > 101){
                            $votingPower = $voteWeight101;
                        } else {
                            $votingPower = $delegate->getVotedBalance();
                        }
                        $ourShare = $liskBalance / $votingPower;
                        $ourRewards = $ourShare * $this->blockRewardPerMonth * ($delegate->getSharingPercentage() / 100);

                        $idealForgeStats[] = [
                            "delegate" => $delegate->getUsername(),
                            "pools" => $delegate->getPools(),
                            "rank" => $delegate->getRank(),
                            "share" => $ourShare,
                            "rewards" => $ourRewards,
                        ];
                        $idealRewards += $ourRewards;

                        $idealDelegateUsernames[] = $delegate->getUsername();

                        if($delegate->getRank() <= 101){
                            $idealRewardsCurrentlyForging += $ourRewards;
                        }
                    }
                }

                $round = 0;
                $roundCount = 0;

                foreach($votedDelegateUsernames as $username){
                    if(!in_array($username, $idealDelegateUsernames)){
                        $idealVotes["unvote"][] = $username;
                        if(!isset($idealVotes["rounds"][$round])){
                            $idealVotes["rounds"][$round] = [
                                "vote" => [],
                                "unvote" => []
                            ];
                        }
                        $idealVotes["rounds"][$round]["unvote"][] = $username;
                        $roundCount++;
                        if($roundCount === 33){
                            $round++;
                            $roundCount = 0;
                        }
                    }
                }

                foreach($idealDelegateUsernames as $username){
                    if(!in_array($username, $votedDelegateUsernames)){
                        $idealVotes["vote"][] = $username;
                        if(!isset($idealVotes["rounds"][$round])){
                            $idealVotes["rounds"][$round] = [
                                "vote" => [],
                                "unvote" => []
                            ];
                        }
                        $idealVotes["rounds"][$round]["vote"][] = $username;
                        $roundCount++;
                        if($roundCount === 33){
                            $round++;
                            $roundCount = 0;
                        }
                    }
                }

                // Sort the array by rewards from high -> low
                usort($idealForgeStats, function($a, $b){
                    return $a["rewards"] < $b["rewards"];
                });
            }
        }

        return $this->render('LiskPoolBundle:RewardCalculator:index.html.twig', [
            "forgeStats" => $forgeStats,
            "optimizedForgeStats" => $finalOptimizedForgeStats,
            "idealForgeStats" => $idealForgeStats,
            "totalRewards" => $totalRewards,
            "optimizedRewards" => $optimizedRewards,
            "idealRewards" => $idealRewards,
            "idealRewardsCurrentlyForging" => $idealRewardsCurrentlyForging,
            "ourBalance" => $liskBalance / 100000000,
            "votedDelegates" => $votedDelegateUsernames,
            "idealVotes" => $idealVotes,
            "hasValidAddress" => $hasValidAddress
        ]);
    }
}
