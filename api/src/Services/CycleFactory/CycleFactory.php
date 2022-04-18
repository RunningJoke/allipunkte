<?php

namespace App\Services\CycleFactory;

use App\Entity\User;
use App\Entity\Cycle;
use App\Entity\Score;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\CycleFactory\CycleFactoryInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class CycleFactory implements CycleFactoryInterface {

    private $em;

    public function __construct(
        EntityManagerInterface $em
    )
    {
        $this->em = $em;
    }

    public function createNewCycle(
        string $name,
        \DateTime $fromDate,
        \DateTime $toDate,
        string $description = "",
        int $costPerPoint = 0,
        int $targetAmount = 0
    ) : Cycle
    {        
        

        $newCycle = new Cycle();
        
        $newCycle   ->setName($name)
                    ->setDescription($description)
                    ->setFromDate($fromDate)
                    ->setToDate($toDate)
                    ->setCostPerPoint($costPerPoint)
                    ->setStatus(Cycle::STATUS_OPEN);
        
        $this->em->persist($newCycle);

        //now add a score entry for each existing user
        $userRepository = $this->em->getRepository(User::class);
        $users = $userRepository->findAll();

        foreach($users as $user) {
            $this->addScoreForUser($newCycle, $user, $targetAmount);
        }



        
        $this->em->flush();

        return $newCycle;
        
        
    }
    
     
    private function addScoreForUser(Cycle $cycle, User $user, int $targetAmount)
    {
        
        $score = new Score();
        $score->setAmount(0);
        $score->setCycle($cycle);
        $score->setUser($user);
        $score->setTargetAmount($targetAmount);
        $score->setPaymentStatus(Score::PAYMENT_NOTSET);
        $this->em->persist($score);
        
    }


}