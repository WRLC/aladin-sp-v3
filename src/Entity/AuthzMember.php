<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\AuthzMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class AuthzMember
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
#[ORM\Entity(repositoryClass: AuthzMemberRepository::class)]
#[UniqueEntity(
    fields: ['institutionService', 'member'],
    message: 'This member is already authorized for this institutional service.'
)]
class AuthzMember
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'authzMembers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?InstitutionService $institutionService = null;

    #[ORM\Column(length: 255)]
    private ?string $member = null;

    /**
     * Get the value of id
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the value of InstitutionService
     *
     * @return InstitutionService|null
     */
    public function getInstitutionService(): ?InstitutionService
    {
        return $this->institutionService;
    }

    /**
     * Set the value of InstitutionService
     *
     * @param InstitutionService|null $institutionService
     *
     * @return $this
     */
    public function setInstitutionService(?InstitutionService $institutionService): AuthzMember
    {
        $this->institutionService = $institutionService;

        return $this;
    }

    /**
     * Get the value of member
     *
     * @return string|null
     */
    public function getMember(): ?string
    {
        return $this->member;
    }

    /**
     * Set the value of member
     *
     * @param string $member
     *
     * @return $this
     */
    public function setMember(string $member): AuthzMember
    {
        $this->member = $member;

        return $this;
    }
}
