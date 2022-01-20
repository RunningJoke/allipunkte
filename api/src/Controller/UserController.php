<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Cycle;
use App\Entity\Score;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private $pwEncoder;
    
    public function __construct(UserPasswordHasherInterface $pwEncoder) {
        $this->pwEncoder = $pwEncoder;
    }
    
    /**
     * @Route("api/users", name="user_create", methods={"POST"})
     */
    public function createNew(Request $request)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        
        $em = $this->getDoctrine()->getManager();
        
        /**
         * @var UserInterface
         */
        $currentUser = $this->getUser();
        
        $jsonArray = json_decode($request->getContent(),true);
        
        $jsonFields = array_keys($jsonArray);
        
        $valid = count(array_diff($jsonFields, ['firstname','lastname','username','mail','license','targetAmount'])) == 0;
        
        if(!$valid)
        {
            return new JsonResponse(['illegal request format'],400);
        }
        
        
        $newUser = new User();
        
        $newUser->setFirstname($jsonArray['firstname']);
        $newUser->setLastname($jsonArray['lastname']);
        $newUser->setUsername($jsonArray['username']);
        $newUser->setMail($jsonArray['mail']);
        $newUser->setLicense($jsonArray['license']);
        
        
        $encodedPassword = $this->pwEncoder->hashPassword($newUser, $jsonArray['license']);
        
        $newUser->setPassword($encodedPassword);
        
        $roleArray = ['ROLE_USER'];
        
        //only admins can grant additional roles
        if(($jsonArray['isAdmin'] ?? false) && $currentUser->isGranted('ROLE_ADMIN')) {
            $roleArray[] = 'ROLE_ADMIN';
        }
        if(($jsonArray['isCreator'] ?? false) && $currentUser->isGranted('ROLE_ADMIN')) {
            $roleArray[] = 'ROLE_CREATOR';
        }
        
        $newUser->setRoles($roleArray);
        
        $this->addScoreEntry($newUser, (int)$jsonArray['targetAmount']);
        
        $em->persist($newUser);
        
        $em->flush();
        
        return new JsonResponse($jsonArray,201);
        
    }
    
     
    private function addScoreEntry(User $user, int $targetAmount,ManagerRegistry $doctrine)
    {
        $entityManager = $doctrine->getManager();
        $currentCycle = $entityManager->getRepository(Cycle::class)->findCurrentCycle();
        
        $score = new Score();
        $score->setAmount(0);
        $score->setCycle($currentCycle);
        $score->setUser($user);
        $score->setTargetAmount($targetAmount);
        $entityManager->persist($score);
        
    }
}
