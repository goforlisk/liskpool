<?php
namespace LiskPoolBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="voters", indexes={@ORM\Index(name="idx_address", columns={"address"})})
 */
class Voter
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $address;

    /**
     * @ORM\Column(type="bigint", options={ "default": 0 })
     */
    private $balance = 0;

    /**
     * @ORM\Column(type="bigint", options={ "default": 0 })
     */
    private $balanceTotal = 0;

    /**
     * @ORM\OneToMany(targetEntity="Payout", mappedBy="voter")
     */
    private $payouts;

    public function __construct() {
        $this->payouts = new ArrayCollection();
    }

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
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * @param mixed $address
     */
    public function setAddress($address)
    {
        $this->address = $address;
    }

    /**
     * @return mixed
     */
    public function getBalance()
    {
        return $this->balance;
    }

    /**
     * @param mixed $balance
     */
    public function setBalance($balance)
    {
        $this->balance = $balance;
    }

    /**
     * @return mixed
     */
    public function getBalanceTotal()
    {
        return $this->balanceTotal;
    }

    /**
     * @param mixed $balanceTotal
     */
    public function setBalanceTotal($balanceTotal)
    {
        $this->balanceTotal = $balanceTotal;
    }

    /**
     * @return mixed
     */
    public function getPayouts()
    {
        return $this->payouts;
    }

    /**
     * @param mixed $payouts
     */
    public function setPayouts($payouts)
    {
        $this->payouts = $payouts;
    }
}