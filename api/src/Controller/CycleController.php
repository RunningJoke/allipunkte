<?php

namespace App\Controller;

use App\Entity\Score;
use App\Repository\ScoreRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\UserFactory\UserFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Services\CycleFactory\CycleFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class CycleController extends AbstractController
{
    private $cycleFactory;
    private $scoreRepository;
    private $normalizer;

    public function __construct(
        CycleFactoryInterface $cycleFactory,
        ScoreRepository $scoreRepository,
        NormalizerInterface $normalizer) {

        $this->cycleFactory = $cycleFactory;
        $this->scoreRepository = $scoreRepository;
        $this->normalizer = $normalizer;
    }
    
    #[Route(path: 'api/cycles', name: 'cycle_create', methods: ['POST'])]
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
                
        
        return $this->json($newCycle, 201, [], ['groups' => ['CYCLE_READ']]);
        
    }

    #[Route(path: 'pastCycles', name: 'cycle_past', methods: ['GET'])]
    public function getPastCycleStatusForUser(Request $request)
    {
        $this->denyAccessUnlessGranted("ROLE_USER");

        /**
         * @var \App\Entity\User
         */
        $currentUser = $this->getUser();

        $allScores = $this->scoreRepository->getPastCycleStates($currentUser);

        // the nested cycle and user keep the API resource groups (legacy output), the score itself is not grouped
        return $this->json(array_map(fn (Score $score) => [
            'id' => $score->getId(),
            'cycle' => $this->normalizer->normalize($score->getCycle(), 'json', ['groups' => ['CYCLE_READ']]),
            'user' => $this->normalizer->normalize($score->getUser(), 'json', ['groups' => ['USER_READ']]),
            'amount' => $score->getAmount(),
            'targetAmount' => $score->getTargetAmount(),
            'paymentStatus' => $score->getPaymentStatus(),
        ], $allScores));
    }
    
}
