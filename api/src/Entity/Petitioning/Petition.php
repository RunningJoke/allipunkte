<?php

namespace App\Entity\Petitioning;

use App\Entity\User;
use Doctrine\ORM\Mapping as ORM;
use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\Petitioning\PetitionRepository;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PetitionRepository::class)
 */
#[ApiResource(
    normalizationContext: ['groups' => ['PETITION_READ']],
    attributes: ['security' => "is_granted('ROLE_USER')", "pagination_enabled" => false],
    itemOperations: [
        "get",
        "put" => ['security_post_denormalize' => "is_granted('PETITION_CAN_UPDATE',previous_object)"]
    ]
 )
 ]
class Petition
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="petitions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $createUser;

    /**
     * @ORM\Column(type="datetime")
     */
    private $createDate;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $title;

    /**
     * @ORM\Column(type="text", options={"default" : ""})
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, options={"default" : "open"})
     */
    private $status;

    /**
     * @ORM\Column(type="datetime")
     */
    private $dueDate;

    /**
     * @ORM\Column(type="integer", options={"default" : 1})
     */
    private $openPositions;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $filledPositions;

    /**
     * @ORM\Column(type="integer", options={"default" : 0})
     */
    private $offeredPoints;

    #[Groups(["PETITION_READ"])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(["PETITION_READ"])]
    public function getCreateUser(): ?User
    {
        return $this->createUser;
    }

    public function setCreateUser(?User $createUser): self
    {
        $this->createUser = $createUser;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getCreateDate(): ?\DateTimeInterface
    {
        return $this->createDate;
    }

    public function setCreateDate(\DateTimeInterface $createDate): self
    {
        $this->createDate = $createDate;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): self
    {
        $this->dueDate = $dueDate;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getOpenPositions(): ?int
    {
        return $this->openPositions;
    }

    public function setOpenPositions(int $openPositions): self
    {
        $this->openPositions = $openPositions;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getFilledPositions(): ?int
    {
        return $this->filledPositions;
    }

    public function setFilledPositions(int $filledPositions): self
    {
        $this->filledPositions = $filledPositions;

        return $this;
    }

    #[Groups(["PETITION_READ"])]
    public function getOfferedPoints(): ?int
    {
        return $this->offeredPoints;
    }

    public function setOfferedPoints(int $offeredPoints): self
    {
        $this->offeredPoints = $offeredPoints;

        return $this;
    }
}
