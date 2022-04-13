<?php

namespace App\Services;

use App\Entity\User;
use App\Entity\Cycle;
use App\Entity\Score;
use App\Services\UserFactoryInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFactory implements UserFactoryInterface {

    private $em;
    private $pwEncoder;

    public function __construct(
        EntityManagerInterface $em,
        UserPasswordHasherInterface $pwEncoder
    )
    {
        $this->em = $em;
        $this->pwEncoder = $pwEncoder;
    }

    public function createNewUser(
        string $firstname,
        string $lastname,
        string $username,
        string $mail,
        string $license,
        int $targetAmount,
        bool $isAdmin = false,
        bool $isCreator = false
    ) : User
    {        
        

        $newUser = new User();
        
        $newUser->setFirstname($firstname);
        $newUser->setLastname($lastname);
        $newUser->setUsername($username);
        $newUser->setMail($mail);
        $newUser->setLicense($license);
        
        
        $encodedPassword = $this->pwEncoder->hashPassword($newUser, $license);
        
        $newUser->setPassword($encodedPassword);
        
        $roleArray = ['ROLE_USER'];
        
        //only admins can grant additional roles
        if($isAdmin) {
            $roleArray[] = 'ROLE_ADMIN';
        }
        if($isCreator) {
            $roleArray[] = 'ROLE_CREATOR';
        }
        
        $newUser->setRoles($roleArray);
        
        $this->addScoreEntry($newUser, (int)$targetAmount);
        
        $this->em->persist($newUser);
        
        $this->em->flush();

        return $newUser;
        
        
    }
    
     
    private function addScoreEntry(User $user, int $targetAmount)
    {
        $currentCycle = $this->em->getRepository(Cycle::class)->findCurrentCycle();

        if($currentCycle === null) {
        //if no cycle is active, no score needs to be added
            return;
        }
        
        $score = new Score();
        $score->setAmount(0);
        $score->setCycle($currentCycle);
        $score->setUser($user);
        $score->setTargetAmount($targetAmount);
        $this->em->persist($score);
        
    }


}