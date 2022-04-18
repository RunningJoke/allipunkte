<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Services\UserFactory\UserFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Services\CycleFactory\CycleFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CycleController extends AbstractController
{
    private $cycleFactory;
    
    public function __construct(
        CycleFactoryInterface $cycleFactory) {

        $this->cycleFactory = $cycleFactory;
    }
    
    /**
     * 
     * 
     * 
     * @Route("api/cycles", name="cycle_create", methods={"POST"})
     */
    public function createNewCycle(Request $request)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        
        $jsonArray = json_decode($request->getContent(),true) ?? [];
        
        $jsonFields = array_keys($jsonArray);
        
        $valid = count(array_diff($jsonFields, 
            [
                'fromDate',
                'toDate',
                'name',
                'description',
                'costPerPoint',
                'targetAmount'
            ])) == 0;
        
        if(!$valid)
        {
            return new JsonResponse(['illegal request format'],400);
        }
        
        $dTtoDate = new \DateTime($jsonArray['toDate']);
        $dTfromDate = new \DateTime($jsonArray['fromDate']);

        $newCycle = $this->cycleFactory->createNewCycle(
            $jsonArray['name'],
            $dTfromDate,
            $dTtoDate,
            $jsonArray['description'],
            (int)$jsonArray['costPerPoint'],
            $jsonArray['targetAmount']
        );
                
        
        return $this->json($newCycle,201);
        
    }
    
}
