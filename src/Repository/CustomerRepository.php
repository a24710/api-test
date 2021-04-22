<?php

namespace App\Repository;

use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;



class CustomerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Customer::class);
    }

    public function recordCount(): int
    {
        $qb = $this->createQueryBuilder('customer');
        $qb->select('COUNT(customer.id)');
        $count = $qb->getQuery()->getSingleScalarResult();

        return $count;
    }
}
