<?php

namespace App\Controller;

use App\Entity\Cycle;
use App\Entity\Transaction;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use ApiPlatform\Core\Api\IriConverterInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;

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
    
    /**
     * @Route("/myScore", name="actualScore", methods={"GET"})
     */
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
    
    
    /**
     * @Route("/transfers", name="own_transfers", methods={"GET"})
     */
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
            $transactionJSON = $this->normalizer->normalize($transactions, 'json');
        } else {
            $transactionJSON = [];
        }
        
        return new JsonResponse($transactionJSON);
    }
    
    /**
     * @Route("/transfer", name="score_transfer", methods={"POST"})
     */
    public function transfer( Request $request)
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        
        /**
         * @var \App\Repository\CycleRepository
         */
        $cycleRepo = $this->em->getRepository(Cycle::class);      
        $currentCycle = $cycleRepo->findCurrentCycle();
        if($currentCycle === null) {
            //stop execution right here
            return new JsonResponse(['userScore' => 0]);
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
        
        /**
         * @var \App\Entity\User
         */
        $transactionTargetUser = $this->iriConverter->getItemFromIri($transactionTargetIRI);

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
