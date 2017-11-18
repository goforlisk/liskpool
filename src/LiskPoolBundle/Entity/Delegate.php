<?php
namespace LiskPoolBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="delegates", indexes={@ORM\Index(name="idx_address", columns={"address"})})
 */
class Delegate
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
     * @ORM\Column(type="string", length=64, unique=true)
     */
    private $username;

    /**
     * @ORM\Column(type="bigint", options={ "default": 0 })
     */
    private $votedBalance = 0;

    /**
     * @ORM\Column(type="integer", options={ "default": 0 })
     */
    private $sharingPercentage = 0;

    /**
     * @ORM\Column(type="integer", options={ "default": 0 })
     */
    private $rank;

    /**
     * @ORM\Column(type="array")
     */
    private $pools = [];

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
    public function getUsername()
    {
        return $this->username;
    }

    /**
     * @param mixed $username
     */
    public function setUsername($username)
    {
        $this->username = $username;
    }

    /**
     * @return mixed
     */
    public function getVotedBalance()
    {
        return $this->votedBalance;
    }

    /**
     * @param mixed $votedBalance
     */
    public function setVotedBalance($votedBalance)
    {
        $this->votedBalance = $votedBalance;
    }

    /**
     * @return mixed
     */
    public function getSharingPercentage()
    {
        return $this->sharingPercentage;
    }

    /**
     * @param mixed $sharingPercentage
     */
    public function setSharingPercentage($sharingPercentage)
    {
        $this->sharingPercentage = $sharingPercentage;
    }

    /**
     * @return mixed
     */
    public function getPools()
    {
        return $this->pools;
    }

    /**
     * @param mixed $pools
     */
    public function setPools($pools)
    {
        $this->pools = $pools;
    }

    /**
     * @return mixed
     */
    public function getRank()
    {
        return $this->rank;
    }

    /**
     * @param mixed $rank
     */
    public function setRank($rank)
    {
        $this->rank = $rank;
    }
}