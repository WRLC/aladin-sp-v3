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
     * @param EntityManagerInterface $em
     */
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, $em->getClassMetadata(Institution::class));
    }
}
