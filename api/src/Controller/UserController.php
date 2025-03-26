<?php

namespace App\Controller;

use App\Entity\Score;
use App\Repository\UserRepository;
use App\Repository\CycleRepository;
use Doctrine\ORM\EntityManagerInterface;
use ApiPlatform\Api\IriConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\UserFactory\UserFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserController extends AbstractController
{
    private UserFactoryInterface $userFactory;
    private UserRepository $userRepository;
    private CycleRepository $cycleRepository;
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $em;
    
    public function __construct(
        UserFactoryInterface $userFactory,
        UserRepository $userRepository,
        CycleRepository $cycleRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em) {

        $this->userFactory = $userFactory;
        $this->userRepository = $userRepository;
        $this->cycleRepository = $cycleRepository;
        $this->passwordHasher = $passwordHasher;
        $this->em = $em;
    }
    
    /**
     * 
     * 
     * 
     * @Route("api/users", name="user_create", methods={"POST"})
     */
    public function createNew(Request $request)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        
        
        /**
         * @var UserInterface
         */
        $currentUser = $this->getUser();
        
        $jsonArray = json_decode($request->getContent(),true);
        
        $jsonFields = array_keys($jsonArray);
        
        $valid = count(array_diff($jsonFields, 
            ['firstname',
            'lastname',
            'username',
            'mail',
            'license',
            'targetAmount',
            'isAdmin',
            'isCreator',
            'password', 'password_confirm'])) == 0;
        
        if(!$valid)
        {
            return new JsonResponse(['illegal request format'],400);
        }

        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        
        $newUser = $this->userFactory->createNewUser(
            $jsonArray['firstname'],
            $jsonArray['lastname'],
            $jsonArray['username'],
            $jsonArray['mail'],
            $jsonArray['license'],
            $jsonArray['targetAmount'],
            $jsonArray['isAdmin'] ?? false,
            $jsonArray['isCreator'] ?? false,
            $jsonArray['password'] ?? ""
        );
                
        
        return new JsonResponse($jsonArray,201);
        
    }


    
    /**
     * 
     * 
     * 
     * @Route("api/users/{id}/setTargetPoints", name="user_update_target_points", methods={"POST"})
     */
    public function setTargetPoints(int $id, Request $request)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        
        $targetUser = $this->userRepository->findOneById($id);
        if($targetUser === null) {
            return $this->json(['status' => 404, 'message' => 'User not found'],404);
        }

        $jsonArray = json_decode($request->getContent(),true);

        if($jsonArray === null ||!isset($jsonArray['newTargetScore'])) {
            return $this->json(['status' => 400, 'message' => 'Invalid request'],400);
        }

        //fetch the score for the active cycle
        /**
         * @var Score
         */
        $currentScore = $targetUser->getScoreForCycle($this->cycleRepository->findCurrentCycle());

        if($currentScore === null) {
            return $this->json(['status' => 404, 'message' => 'No active cycle score found'],404);
        }

        //set the score target to the new value
        $currentScore->setTargetAmount((int)$jsonArray['newTargetScore']);

        $this->em->flush();
        
        return new JsonResponse(['targetScore' =>  $currentScore->getTargetAmount()],200);
        
    }

    /**
     * @Route("/updatePassword", name="user_update_password", methods={"POST"})
     */
    public function updatePassword(Request $request, Security $security)
    {        
        
        /**
         * @var UserInterface
         */
        $currentUser = $security->getUser();
        
        $jsonArray = json_decode($request->getContent(),true);
        
        if((!isset($jsonArray['oldPassword']) || !isset($jsonArray['newPassword']) || !isset($jsonArray['newPasswordVerification']) ||
        $jsonArray['newPassword'] !== $jsonArray['newPasswordVerification'])) {
            return $this->json(['status' => 400, 'message' => 'invalid request'],400);
        }



        $newEncodedPassword = $this->passwordHasher->hashPassword($currentUser, $jsonArray['newPassword']);
        
        $currentUser->setPassword($newEncodedPassword);

        $this->em->flush();
                
        
        return new JsonResponse(['status' => 200, 'message' => 'password updated'],200);
        
    }

    
    /**
     * @Route("/updatePasswordAdmin", name="user_update_password_admin", methods={"POST"})
     */
    public function updatePasswordAsAdmin(Request $request, IriConverterInterface $converter)
    {        
        $this->denyAccessUnlessGranted("ROLE_ADMIN");

        $jsonArray = json_decode($request->getContent(),true);
        
        if((!isset($jsonArray['targetUser']) || !isset($jsonArray['newPassword']) || !isset($jsonArray['newPasswordVerification']) ||
        $jsonArray['newPassword'] !== $jsonArray['newPasswordVerification'])) {
            return $this->json(['status' => 400, 'message' => 'invalid request'],400);
        }

        $targetUser = $converter->getResourceFromIri($jsonArray['targetUser']);

        $newEncodedPassword = $this->passwordHasher->hashPassword($targetUser, $jsonArray['newPassword']);
        
        $targetUser->setPassword($newEncodedPassword);

        $this->em->flush();
                
        
        return new JsonResponse(['status' => 200, 'message' => 'password updated'],200);
        
    }

    
}
