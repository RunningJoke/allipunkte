<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    private UserPasswordHasherInterface $passwordHasher;
    private EntityManagerInterface $em;
    
    public function __construct(
        UserFactoryInterface $userFactory,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em) {

        $this->userFactory = $userFactory;
        $this->userRepository = $userRepository;
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
            'isCreator'])) == 0;
        
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
            $jsonArray['isCreator'] ?? false
        );
                
        
        return new JsonResponse($jsonArray,201);
        
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
        
        if(!isset($jsonArray['oldPassword']) || !isset($jsonArray['newPassword']) || !isset($jsonArray['newPasswordVerification']) ||
        $jsonArray['newPassword'] !== $jsonArray['newPasswordVerification']) {
            return $this->json(['status' => 400, 'message' => 'invalid request'],400);
        }



        $newEncodedPassword = $this->passwordHasher->hashPassword($currentUser, $jsonArray['newPassword']);
        
        $currentUser->setPassword($newEncodedPassword);

        $this->em->flush();
                
        
        return new JsonResponse(['status' => 200, 'message' => 'password updated'],200);
        
    }

    
}
