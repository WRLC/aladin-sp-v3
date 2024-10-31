<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\AuthzMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


/**
 * Class AuthzMember
 */
#[ORM\Entity(repositoryClass: AuthzMemberRepository::class)]
#[UniqueEntity(
    fields: ['InstitutionService', 'member'],
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
    private ?InstitutionService $InstitutionService = null;

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
        return $this->InstitutionService;
    }

    /**
     * Set the value of InstitutionService
     *
     * @param InstitutionService|null $InstitutionService
     *
     * @return $this
     */
    public function setInstitutionService(?InstitutionService $InstitutionService): AuthzMember
    {
        $this->InstitutionService = $InstitutionService;

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
