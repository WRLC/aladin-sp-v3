<?php

namespace App\Repository;

use App\Entity\Institution;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Sortable\Entity\Repository\SortableRepository;

/**
 * @extends SortableRepository<Institution>
 */
class InstitutionRepository extends SortableRepository  // @phpstan-ignore-line
{
    /**
     * InstitutionRepository constructor
     *
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, $entityManager->getClassMetadata(Institution::class));
    }
}
