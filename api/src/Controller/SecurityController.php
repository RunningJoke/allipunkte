<?php

namespace App\Controller;

use App\Entity\Cycle;
use App\Entity\Score;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SecurityController extends AbstractController
{
    /**
     * @Route("/security", name="security")
     */
    public function index()
    {
        return $this->render('security/index.html.twig', [
            'controller_name' => 'SecurityController',
        ]);
    }
    
    /**
     * @Route("/jlogin", name="jlogin")
     */
    public function login()  : Response
    {
        return new JsonResponse(['status' => 200, 'message' => 'authentication successful']);
    }
    
    /**
     * @Route("/", name="landing")
     */
    public function landing() : Response
    {
        return new JsonResponse([]);
    }
    
    /**
     * @Route("/jlogout", name="jlogout")
     */
    public function logout() 
    {
        return new JsonResponse(['status' => 200, 'message' => 'logout successful']);
    }
    
    /**
     * @Route("/updateUserData", name="userData")
     * @return JsonResponse
     */
    public function updateUserData(ManagerRegistry $doctrine) : Response
    {
        $this->denyAccessUnlessGranted("ROLE_USER");
        
        /**
         * @var \App\Entity\User
         */
        $currentUser = $this->getUser();
        
        $currentSeason = $doctrine->getManager()->getRepository(Cycle::class)->findCurrentCycle();
        
        $currentSeasonArray = [];
        $currentUserScore = null;

        if($currentSeason !== null) {
            $currentSeasonArray = [
                'name' => $currentSeason->getName(),
                'description' => $currentSeason->getDescription(),
                'from' => $currentSeason->getFromDate()->format(\DateTimeInterface::ISO8601),
                'to' => $currentSeason->getToDate()->format(\DateTimeInterface::ISO8601)
            ];
            $currentUserScore = $currentUser->getScoreForCycle($currentSeason);
        } else {
            $currentSeasonArray = [
                'name' => "Keine aktive Saison",
                'description' => "es ist aktuell keine laufende Saison hinterlegt",
                'from' => (new \DateTime())->format(\DateTimeInterface::ISO8601),
                'to' => (new \DateTime())->format(\DateTimeInterface::ISO8601)
            ];
            $currentUserScore = (new Score())->setAmount(0)->setTargetAmount(0);
        }

        
        $loginResponse = [
            'id' => $currentUser->getId(),
            'username' => $currentUser->getUsername(),
            'firstname' => $currentUser->getFirstname(),
            'lastname' => $currentUser->getLastname(),
            'mail' => $currentUser->getMail(),
            'license' => $currentUser->getLicense(),
            'userScore' => $currentUserScore->getAmount(),
            'targetScore' => $currentUserScore->getTargetAmount(),
            'cycle' => $currentSeasonArray,
            'isAdmin' =>$currentUser->isIsAdmin(),
            'isCreator' => $currentUser->isIsCreator()
        ];
        
        
        return new JsonResponse($loginResponse, 200);
    }
    
}
