<?php

namespace App\Entity;

use App\Repository\AuthzMemberRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


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

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInstitutionService(): ?InstitutionService
    {
        return $this->InstitutionService;
    }

    public function setInstitutionService(?InstitutionService $InstitutionService): static
    {
        $this->InstitutionService = $InstitutionService;

        return $this;
    }

    public function getMember(): ?string
    {
        return $this->member;
    }

    public function setMember(string $member): static
    {
        $this->member = $member;

        return $this;
    }
}
