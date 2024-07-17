<?php

namespace App\Entity;

use App\Repository\AuthzTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuthzTypeRepository::class)]
class AuthzType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $description = null;

    /**
     * @var Collection<int, InstitutionService>
     */
    #[ORM\OneToMany(targetEntity: InstitutionService::class, mappedBy: 'AuthzType')]
    private Collection $authzTypeInstitutionServices;

    public function __construct()
    {
        $this->authzTypeInstitutionServices = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection<int, InstitutionService>
     */
    public function getAuthzTypeInstitutionServices(): Collection
    {
        return $this->authzTypeInstitutionServices;
    }

    public function addAuthzTypeInstitutionService(InstitutionService $authzTypeInstitutionService): static
    {
        if (!$this->authzTypeInstitutionServices->contains($authzTypeInstitutionService)) {
            $this->authzTypeInstitutionServices->add($authzTypeInstitutionService);
            $authzTypeInstitutionService->setAuthzType($this);
        }

        return $this;
    }

    public function removeAuthzTypeInstitutionService(InstitutionService $authzTypeInstitutionService): static
    {
        if ($this->authzTypeInstitutionServices->removeElement($authzTypeInstitutionService)) {
            // set the owning side to null (unless already changed)
            if ($authzTypeInstitutionService->getAuthzType() === $this) {
                $authzTypeInstitutionService->setAuthzType(null);
            }
        }

        return $this;
    }
}
