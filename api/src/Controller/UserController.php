<?php

namespace App\Controller;

use App\Services\UserFactory\UserFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    private $userFactory;
    
    public function __construct(
        UserFactoryInterface $userFactory) {

        $this->userFactory = $userFactory;
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
    
}
