<?php

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ServiceRepository::class)]
class Service
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $slug = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $callback_path = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $legacy_login_path = null;

    #[ORM\Column]
    private ?bool $use_wrInstitution = null;

    /**
     * @var Collection<int, InstitutionService>
     */
    #[ORM\OneToMany(targetEntity: InstitutionService::class, mappedBy: 'Service')]
    private Collection $serviceInstitutions;

    public function __construct()
    {
        $this->serviceInstitutions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): static
    {
        $this->slug = $slug;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getCallbackPath(): ?string
    {
        return $this->callback_path;
    }

    public function setCallbackPath(?string $callback_path): static
    {
        $this->callback_path = $callback_path;

        return $this;
    }

    public function getLegacyLoginPath(): ?string
    {
        return $this->legacy_login_path;
    }

    public function setLegacyLoginPath(?string $legacy_login_path): static
    {
        $this->legacy_login_path = $legacy_login_path;

        return $this;
    }

    public function isUseWrInstitution(): ?bool
    {
        return $this->use_wrInstitution;
    }

    public function setUseWrInstitution(bool $use_wrInstitution): static
    {
        $this->use_wrInstitution = $use_wrInstitution;

        return $this;
    }

    /**
     * @return Collection<int, InstitutionService>
     */
    public function getServiceInstitutions(): Collection
    {
        return $this->serviceInstitutions;
    }

    public function addServiceInstitution(InstitutionService $serviceInstitution): static
    {
        if (!$this->serviceInstitutions->contains($serviceInstitution)) {
            $this->serviceInstitutions->add($serviceInstitution);
            $serviceInstitution->setService($this);
        }

        return $this;
    }

    public function removeServiceInstitution(InstitutionService $serviceInstitution): static
    {
        if ($this->serviceInstitutions->removeElement($serviceInstitution)) {
            // set the owning side to null (unless already changed)
            if ($serviceInstitution->getService() === $this) {
                $serviceInstitution->setService(null);
            }
        }

        return $this;
    }
}
