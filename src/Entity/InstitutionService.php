<?php

namespace App\Entity;

use App\Repository\InstitutionServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: InstitutionServiceRepository::class)]
#[UniqueEntity(
    fields: ['Institution', 'Service'],
    message: 'This service is already associated with the institution.'
)]
class InstitutionService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'InstitutionServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Institution $Institution = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'serviceInstitutions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $Service = null;

    #[ORM\Column(length: 255)]
    private ?string $AuthzType = null;

    #[ORM\Column(length: 255)]
    private ?string $id_attribute = null;

    /**
     * @var Collection<int, AuthzMember>
     */
    #[ORM\OneToMany(targetEntity: AuthzMember::class, mappedBy: 'InstitutionService', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $authzMembers;

    public function __construct()
    {
        $this->authzMembers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getInstitution(): ?Institution
    {
        return $this->Institution;
    }

    public function setInstitution(?Institution $Institution): static
    {
        $this->Institution = $Institution;

        return $this;
    }

    public function getService(): ?Service
    {
        return $this->Service;
    }

    public function setService(?Service $Service): static
    {
        $this->Service = $Service;

        return $this;
    }

    public function getAuthzType(): ?string
    {
        return $this->AuthzType;
    }

    public function setAuthzType(?string $AuthzType): static
    {
        $this->AuthzType = $AuthzType;

        return $this;
    }

    public function getIdAttribute(): ?string
    {
        return $this->id_attribute;
    }

    public function setIdAttribute(string $id_attribute): static
    {
        $this->id_attribute = $id_attribute;

        return $this;
    }

    /**
     * @return Collection<int, AuthzMember>
     */
    public function getAuthzMembers(): Collection
    {
        return $this->authzMembers;
    }

    public function addAuthzMember(AuthzMember $authzMember): static
    {
        if (!$this->authzMembers->contains($authzMember)) {
            $this->authzMembers->add($authzMember);
            $authzMember->setInstitutionService($this);
        }

        return $this;
    }

    public function removeAuthzMember(AuthzMember $authzMember): static
    {
        if ($this->authzMembers->removeElement($authzMember)) {
            // set the owning side to null (unless already changed)
            if ($authzMember->getInstitutionService() === $this) {
                $authzMember->setInstitutionService(null);
            }
        }

        return $this;
    }
}
