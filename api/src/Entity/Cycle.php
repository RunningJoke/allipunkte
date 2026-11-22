<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Put;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: \App\Repository\CycleRepository::class)]
#[ApiResource(
    operations: [
        new GetCollection(),
        new Get(),
        new Put(security: "is_granted('ROLE_ADMIN')"),
    ],
    normalizationContext: ['groups' => ['CYCLE_READ']],
    security: "is_granted('ROLE_USER')",
)]
class Cycle
{

    /**
     * a cycle with open status can still accept transactions
     */
    const STATUS_OPEN = 0;

    /**
     * a closed cycle should no longer accept new transactions and be locked in its current state
     */
    const STATUS_CLOSED = 1;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $name;

    #[ORM\Column(type: 'string', length: 255)]
    private $description;

    #[ORM\Column(type: 'date')]
    private $from_date;

    #[ORM\Column(type: 'date')]
    private $to_date;

    #[ORM\OneToMany(targetEntity: \App\Entity\Score::class, mappedBy: 'cycle', orphanRemoval: true)]
    private $scores;

    #[ORM\OneToMany(targetEntity: \App\Entity\Transaction::class, mappedBy: 'cycle', orphanRemoval: true)]
    private $transactions;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $status;

    /**
     * cost per point is saving the data in cents!
     */
    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    private $costPerPoint;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
        $this->transactions = new ArrayCollection();
    }

    /**
     * @return int|null
     *
     */
    #[Groups(['CYCLE_READ', 'USER_READ_ADMIN'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['CYCLE_READ'])]
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    #[Groups(['CYCLE_READ'])]
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    #[Groups(['CYCLE_READ'])]
    public function getFromDate(): ?\DateTimeInterface
    {
        return $this->from_date;
    }

    public function setFromDate(\DateTimeInterface $from_date): self
    {
        // DBAL 4 'date' columns only accept \DateTime, the serializer hands over \DateTimeImmutable
        $this->from_date = \DateTime::createFromInterface($from_date);

        return $this;
    }

    #[Groups(['CYCLE_READ'])]
    public function getToDate(): ?\DateTimeInterface
    {
        return $this->to_date;
    }

    public function setToDate(\DateTimeInterface $to_date): self
    {
        $this->to_date = \DateTime::createFromInterface($to_date);

        return $this;
    }

    /**
     * @return Collection|Score[]
     */
    public function getScores(): Collection
    {
        return $this->scores;
    }

    public function addScore(Score $score): self
    {
        if (!$this->scores->contains($score)) {
            $this->scores[] = $score;
            $score->setCycle($this);
        }

        return $this;
    }

    public function removeScore(Score $score): self
    {
        if ($this->scores->contains($score)) {
            $this->scores->removeElement($score);
            // set the owning side to null (unless already changed)
            if ($score->getCycle() === $this) {
                $score->setCycle(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function addTransaction(Transaction $transaction): self
    {
        if (!$this->transactions->contains($transaction)) {
            $this->transactions[] = $transaction;
            $transaction->setCycle($this);
        }

        return $this;
    }

    public function removeTransaction(Transaction $transaction): self
    {
        if ($this->transactions->contains($transaction)) {
            $this->transactions->removeElement($transaction);
            // set the owning side to null (unless already changed)
            if ($transaction->getCycle() === $this) {
                $transaction->setCycle(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?int
    {
        return $this->status;
    }

    public function setStatus(int $status): self
    {
        $this->status = $status;

        return $this;
    }

    #[Groups(['CYCLE_READ'])]
    public function getCostPerPoint(): ?int
    {
        return $this->costPerPoint;
    }

    public function setCostPerPoint(int $costPerPoint): self
    {
        $this->costPerPoint = $costPerPoint;

        return $this;
    }
}
