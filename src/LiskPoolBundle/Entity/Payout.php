<?php
namespace LiskPoolBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="payouts")
 * @ORM\HasLifecycleCallbacks()
 */
class Payout
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Voter", inversedBy="payouts")
     * @ORM\JoinColumn(name="voter_id", referencedColumnName="id")
     */
    private $voter;

    /**
     * @ORM\Column(type="bigint")
     */
    private $amount;

    /**
     * @ORM\Column(type="bigint")
     */
    private $fee;

    /**
     * @ORM\Column(type="string", length=100)
     */
    private $transactionId;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datetime;

    /**
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param mixed $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return mixed
     */
    public function getVoter()
    {
        return $this->voter;
    }

    /**
     * @param mixed $voter
     */
    public function setVoter($voter)
    {
        $this->voter = $voter;
    }

    /**
     * @return mixed
     */
    public function getAmount()
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount($amount)
    {
        $this->amount = $amount;
    }

    /**
     * @return mixed
     */
    public function getFee()
    {
        return $this->fee;
    }

    /**
     * @param mixed $fee
     */
    public function setFee($fee)
    {
        $this->fee = $fee;
    }

    /**
     * @return mixed
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @param mixed $transactionId
     */
    public function setTransactionId($transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return mixed
     */
    public function getDatetime()
    {
        return $this->datetime;
    }

    /**
     * @ORM\PrePersist
     */
    public function setDatetime(){
        $this->datetime = new \DateTime();
    }
}