<?php
namespace LiskPoolBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="delegates_history")
 * @ORM\HasLifecycleCallbacks()
 */
class DelegateHistory
{
    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="Delegate", inversedBy="history")
     * @ORM\JoinColumn(name="delegate_id", referencedColumnName="id")
     */
    private $delegate;

    /**
     * @ORM\Column(type="bigint")
     */
    private $votedBalance;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dateTime;

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
    public function getDelegate()
    {
        return $this->delegate;
    }

    /**
     * @param mixed $delegate
     */
    public function setDelegate($delegate)
    {
        $this->delegate = $delegate;
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
     * @ORM\PrePersist
     */
    public function setDateTime()
    {
        $this->dateTime = new \DateTime();
    }
}