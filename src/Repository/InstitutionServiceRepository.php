<?php

namespace App\Repository;

use App\Entity\InstitutionService;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class InstitutionServiceRepository
 *
 * @extends ServiceEntityRepository<InstitutionService>
 */
class InstitutionServiceRepository extends ServiceEntityRepository
{
    /**
     * InstitutionServiceRepository constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InstitutionService::class);
    }
}
