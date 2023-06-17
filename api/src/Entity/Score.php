<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiProperty;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ScoreRepository")
 */
class Score
{

    /**
     * PAYMENT_NOTSET is the default value for active scores.
     * During a cycle, the score should not request any payment.
     * Closing a cycle should set the payment to either NOTREQUIRED or PENDING
     */
    const PAYMENT_NOTSET = 0;
    
    /**
     * PAYMENT_NOTREQUIRED is the value the score should get if the user achieved the targetAmount
     * and the cycle is closed
     */
    const PAYMENT_NOTREQUIRED = 1;

    /**
     * PAYMENT_PENDING should be set, if the cycle is closed and the target amount was not achieved
     */
    const PAYMENT_PENDING = 2;

    /**
     * PAYMENT_FULFILLED this must be set if the payment status was pending and the payment was processed
     */
    const PAYMENT_FULFILLED = 3;


    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Cycle", inversedBy="scores")
     * @ORM\JoinColumn(nullable=false)
     */
    private $cycle;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="scores")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $amount;

    /**
     * @ORM\Column(type="integer")
     */
    private $targetAmount;

    /**
     * @ORM\Column(type="integer", nullable=true, options={"default" : 0})
     */
    private $paymentStatus;

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getCycle(): ?Cycle
    {
        return $this->cycle;
    }

    public function setCycle(?Cycle $cycle): self
    {
        $this->cycle = $cycle;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(?int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getTargetAmount(): ?int
    {
        return $this->targetAmount;
    }

    public function setTargetAmount(int $targetAmount): self
    {
        $this->targetAmount = $targetAmount;

        return $this;
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getPaymentStatus(): ?int
    {
        return $this->paymentStatus;
    }

    public function setPaymentStatus(?int $paymentStatus): self
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }
}
