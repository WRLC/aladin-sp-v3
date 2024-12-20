<?php

namespace App\Repository;

use App\Entity\AuthzMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class AuthzMemberRepository
 *
 * @extends ServiceEntityRepository<AuthzMember>
 */
class AuthzMemberRepository extends ServiceEntityRepository
{
    /**
     * AuthzMemberRepository constructor
     *
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AuthzMember::class);
    }
}
