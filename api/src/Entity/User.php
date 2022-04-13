<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use ApiPlatform\Core\Annotation\ApiResource;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ApiResource(
 * normalizationContext={"groups"={"USER_READ"}},
 * attributes={"security"="is_granted('ROLE_USER')"},
 *     collectionOperations={
 *         "get"
 *     },
 *     itemOperations={
 *         "get",
 *         "put"={"security"="is_granted('ROLE_ADMIN')"},
 *     }))
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @UniqueEntity(fields={"username"}, message="There is already an account with this username")
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $firstname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $lastname;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $license;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $password;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $mail;

    /**
     * @ORM\Column(type="json", nullable=true)
     */
    private $roles = [];

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Score", mappedBy="user")
     */
    private $scores;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="origin")
     */
    private $sentTransactions;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Transaction", mappedBy="target")
     */
    private $receivedTransactions;

    public function __construct()
    {
        $this->scores = new ArrayCollection();
        $this->sentTransactions = new ArrayCollection();
        $this->receivedTransactions = new ArrayCollection();
    }

    /**
     * @Groups({"USER_READ"})
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @Groups({"USER_READ"})
     */
    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): self
    {
        $this->firstname = $firstname;

        return $this;
    }

    /**
     * @Groups({"USER_READ"})
     */
    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): self
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getUserIdentifier(): string
    {
        return $this->getUsername();
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getLicense(): ?string
    {
        return $this->license;
    }


    public function setLicense(string $license): self
    {
        $this->license = $license;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function getMail(): ?string
    {
        return $this->mail;
    }

    public function setMail(string $mail): self
    {
        $this->mail = $mail;

        return $this;
    }

    public function eraseCredentials() {
        
    }

    
    public function getRoles() : array {
        //always add the user role to an authenticated user           
        return  array_merge($this->roles,["ROLE_USER"]);
    }

    public function setRoles(?array $roles): self
    {
        $this->roles = $roles;
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
            $score->setUser($this);
        }

        return $this;
    }

    public function removeScore(Score $score): self
    {
        if ($this->scores->contains($score)) {
            $this->scores->removeElement($score);
            // set the owning side to null (unless already changed)
            if ($score->getUser() === $this) {
                $score->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getSentTransactions(): Collection
    {
        return $this->sentTransactions;
    }

    public function addSentTransaction(Transaction $sentTransaction): self
    {
        if (!$this->sentTransactions->contains($sentTransaction)) {
            $this->sentTransactions[] = $sentTransaction;
            $sentTransaction->setOrigin($this);
        }

        return $this;
    }

    public function removeSentTransaction(Transaction $sentTransaction): self
    {
        if ($this->sentTransactions->contains($sentTransaction)) {
            $this->sentTransactions->removeElement($sentTransaction);
            // set the owning side to null (unless already changed)
            if ($sentTransaction->getOrigin() === $this) {
                $sentTransaction->setOrigin(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Transaction[]
     */
    public function getReceivedTransactions(): Collection
    {
        return $this->receivedTransactions;
    }

    public function addReceivedTransaction(Transaction $receivedTransaction): self
    {
        if (!$this->receivedTransactions->contains($receivedTransaction)) {
            $this->receivedTransactions[] = $receivedTransaction;
            $receivedTransaction->setTarget($this);
        }

        return $this;
    }

    public function removeReceivedTransaction(Transaction $receivedTransaction): self
    {
        if ($this->receivedTransactions->contains($receivedTransaction)) {
            $this->receivedTransactions->removeElement($receivedTransaction);
            // set the owning side to null (unless already changed)
            if ($receivedTransaction->getTarget() === $this) {
                $receivedTransaction->setTarget(null);
            }
        }

        return $this;
    }

    
    public function getScoreForCycle(Cycle $cycle)
    {
        return $this->getScores()
                ->filter(function($score) use (&$cycle) { return ($score->getCycle() === $cycle); })
                ->first();
                
    }

    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function isIsCreator() : bool
    {
        return in_array('ROLE_CREATOR',$this->getRoles());
    }
    
    /**
     * @Groups({"USER_READ_ADMIN"})
     */
    public function isIsAdmin() : bool
    {
        return in_array('ROLE_ADMIN',$this->getRoles());
    }
    
}
