<?php

namespace App\Controller;

use App\Entity\Cycle;
use App\Entity\Transaction;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use ApiPlatform\Metadata\IriConverterInterface;
use ApiPlatform\Metadata\Exception\InvalidArgumentException;
use ApiPlatform\Metadata\Exception\ItemNotFoundException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class ScoreController extends AbstractController
{
    private $em;
    private $iriConverter;
    private $normalizer;
    
    public function __construct(EntityManagerInterface $em, IriConverterInterface $iriConverter, NormalizerInterface $normalizer) {
        $this->em = $em;
        $this->iriConverter = $iriConverter;
        $this->normalizer = $normalizer;
    }
    
    #[Route(path: '/myScore', name: 'actualScore', methods: ['GET'])]
    public function getScoreOfLoggedInUser(Request $request) {
        $this->denyAccessUnlessGranted("ROLE_USER");
        
        
        $currentUser = $this->getUser();
        $currentCycle = $this->em->getRepository(\App\Entity\Cycle::class)->findCurrentCycle();
        
        if($currentCycle === null)
        {
            return new JsonResponse(['userScore' => 0]);
        }

        $userScore = $currentUser->getScoreForCycle($currentCycle);
        
        return new JsonResponse(['userScore' => $userScore->getAmount()]);
    }

    
    #[Route(path: 'api/scores/{id}/setPaymentStatus', name: 'score_update_payment_status', methods: ['POST'])]
    public function setPaymentStatus(int $id, Request $request)
    {
        $this->denyAccessUnlessGranted("ROLE_ADMIN");
        //fetch score entry
        $scoreEntry = $this->em->getRepository(\App\Entity\Score::class)->findOneById($id);
        if($scoreEntry === null) {
            throw $this->createNotFoundException('score not found');
        }
        $jsonArray = json_decode($request->getContent(),true);
        $scoreEntry->setPaymentStatus($jsonArray['newPaymentStatus']);
        $this->em->flush();

        return new JsonResponse(['paymentStatus' => $scoreEntry->getPaymentStatus()]);
    }

    
    
    #[Route(path: '/transfers', name: 'own_transfers', methods: ['GET'])]
    public function getList( Request $request)
    {
        $this->denyAccessUnlessGranted("ROLE_USER");
        $currentUser = $this->getUser();
        
        $currentCycle = $this->em->getRepository(\App\Entity\Cycle::class)->findCurrentCycle();
        
        /**
         * @var \App\Repository\TransactionRepository
         */
        $transactionRepo = $this->em->getRepository(Transaction::class);
        
        if($currentCycle !== null) {
            $transactions = $transactionRepo->findOwnTransactions($currentUser, $currentCycle);
            $transactionJSON = array_map([$this, 'transactionToArray'], $transactions);
        } else {
            $transactionJSON = [];
        }
        
        return new JsonResponse($transactionJSON);
    }
    
    /**
     * Builds the response format by hand, so related entities are always referenced by IRI
     * (e.g. "/users/1") and don't depend on the serializer configuration or version.
     */
    private function transactionToArray(Transaction $transaction): array
    {
        return [
            'id' => $transaction->getId(),
            'origin' => $this->iriConverter->getIriFromResource($transaction->getOrigin()),
            'target' => $this->iriConverter->getIriFromResource($transaction->getTarget()),
            'description' => $transaction->getDescription(),
            'amount' => $transaction->getAmount(),
            'timestamp' => $transaction->getTimestamp()->format(\DateTimeInterface::ATOM),
            'cycle' => $this->iriConverter->getIriFromResource($transaction->getCycle()),
            'createdPoints' => $transaction->getCreatedPoints(),
        ];
    }

    #[Route(path: '/transfer', name: 'score_transfer', methods: ['POST'])]
    public function transfer( Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /**
         * @var \App\Repository\CycleRepository
         */
        $cycleRepo = $this->em->getRepository(Cycle::class);      
        $currentCycle = $cycleRepo->findCurrentCycle();
        //dont allow transactions when there is no cycle or the current cycle has been closed.
        //Answer with an error so the client can tell that nothing was booked (#65)
        if($currentCycle === null || $currentCycle->getStatus() == Cycle::STATUS_CLOSED) {
            throw new ConflictHttpException('Keine aktive Saison');
        }
           
        $requestBody = $request->getContent();        
        $jsonArray = json_decode($requestBody, true);

        if($jsonArray === null) {
            //invalid json format, abort request
            throw new BadRequestException("invalid json format");
        }
        
        foreach($jsonArray as $transaction)
        {
            $transactionObject = $this->buildTransaction($transaction, $currentCycle);   
            $this->transferPoints($transactionObject,$currentCycle);   
        }

        //abort if sending user now has negative points
        $sendingUserScore = $this->getUser()->getScoreForCycle($currentCycle)->getAmount();
        if($sendingUserScore < 0)   {
            throw new BadRequestException("user does not have enough points");
        }
        
        $this->em->flush();
        
        return new JsonResponse(['userScore' => $sendingUserScore]);
    }

    private function transferPoints(Transaction $transaction, Cycle $currentCycle)
    {
        //subtract points from origin
        $sendingUserScore = $this->getUser()->getScoreForCycle($currentCycle);    
        
        if(!$transaction->getCreatedPoints()) {
            $sendingUserScore->setAmount($sendingUserScore->getAmount() - $transaction->getAmount());
        }

        //add points to target
        $targetUserScore = $transaction->getTarget()->getScoreForCycle($currentCycle);
        $targetUserScore->setAmount($targetUserScore->getAmount() + $transaction->getAmount());
    }

    private function buildTransaction(array $transaction, Cycle $currentCycle) : ?Transaction
    {
        /**
         * @var \App\Entity\User
         */
        $transactionOriginUser = $this->getUser();
                
        $canCreateScores = $this->isGranted("ROLE_ADMIN") || $this->isGranted("ROLE_CREATOR");
        $transactionTargetIRI = $transaction['target'];   
        
        //an unknown or malformed target IRI is invalid input, not a server error (#66)
        try {
            $transactionTargetUser = $this->iriConverter->getResourceFromIri($transactionTargetIRI);
        } catch (ItemNotFoundException|InvalidArgumentException) {
            $transactionTargetUser = null;
        }
        if(!$transactionTargetUser instanceof User) {
            throw new BadRequestException("unknown target user");
        }

        $transactionAmount = $transaction['amount'];          
        $transactionDescription = $transaction['description'];
        
        $transactionCreatePoints = $transaction['createPoints'] ?? false;

        $transactionAmountInt = 0;
        if(is_numeric($transactionAmount)) {
            $transactionAmountInt = (int)$transactionAmount;
        }

        //cant give negative points
        if($transactionAmountInt <= 0) { throw new BadRequestException("can't send negative points"); }

        //cant send points to himself
        if($transactionOriginUser === $transactionTargetUser) { throw new BadRequestException("can't send points to yourself"); }

        //insufficient rights to create points
        if($transactionCreatePoints && !$canCreateScores) { throw new BadRequestException("not allowed to create points"); }
        
        $transactionEntity = new Transaction();        
        $transactionEntity->setCycle($currentCycle);
        $transactionEntity->setOrigin($transactionOriginUser);
        $transactionEntity->setTarget($transactionTargetUser);
        $transactionEntity->setDescription($transactionDescription);
        $transactionEntity->setAmount($transactionAmountInt);
        $transactionEntity->setCreatedPoints($transactionCreatePoints);

        
        $this->em->persist($transactionEntity);

        return $transactionEntity;
    }
}
