<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\TransactionRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class, inversedBy: 'sentTransactions')]
    private $origin;

    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: \App\Entity\User::class, inversedBy: 'receivedTransactions')]
    private $target;

    #[ORM\Column(type: 'string', length: 255)]
    private $description;

    #[ORM\Column(type: 'integer')]
    private $amount;
    
    #[ORM\Column(type: 'datetime')]
    private $timestamp;
    
    #[ORM\Column(type: 'boolean')]
    private $created_points;
    
    #[ORM\JoinColumn(nullable: false)]
    #[ORM\ManyToOne(targetEntity: \App\Entity\Cycle::class, inversedBy: 'transactions')]
    private $cycle;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCreatedPoints() : bool
    {
        return $this->created_points;
    }
    
    public function setCreatedPoints(bool $createdPoints) :self 
    {
        $this->created_points = $createdPoints;
        
        return $this;
    }
    
    
    public function getOrigin(): ?User
    {
        return $this->origin;
    }

    public function setOrigin(?User $origin): self
    {
        $this->origin = $origin;

        return $this;
    }

    public function getTarget(): ?User
    {
        return $this->target;
    }

    public function setTarget(?User $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAmount(): ?int
    {
        return $this->amount;
    }

    public function setAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function getCycle(): ?Cycle
    {
        return $this->cycle;
    }

    public function setCycle(?Cycle $cycle): self
    {
        $this->cycle = $cycle;

        return $this;
    }
    
    public function getTimestamp() : \DateTime
    {
        return $this->timestamp;
    }
    
    #[ORM\PrePersist]
    public function addTimestamp() {
        $this->timestamp = new \DateTime();
    }
    
}
