<?php

namespace App\Repository;

use App\Entity\Cycle;
use Doctrine\DBAL\Types\Type;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @method Cycle|null find($id, $lockMode = null, $lockVersion = null)
 * @method Cycle|null findOneBy(array $criteria, array $orderBy = null)
 * @method Cycle[]    findAll()
 * @method Cycle[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CycleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Cycle::class);
    }

    public function findCurrentCycle(): ?Cycle
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.from_date <= :val')
            ->andWhere('c.to_date > :val')
            ->setParameter('val', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findActiveAndFutureCycles()
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.from_date <= :val')
            ->setParameter('val', new \DateTime())
            ->getQuery()
            ->getResult()
        ;
    }
    
    // /**
    //  * @return Cycle[] Returns an array of Cycle objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    
}
