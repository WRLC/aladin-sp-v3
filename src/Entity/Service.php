<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\ServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Service
 */
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

    /**
     * @var Collection<int, InstitutionService>
     */
    #[ORM\OneToMany(targetEntity: InstitutionService::class, mappedBy: 'Service')]
    private Collection $serviceInstitutions;

    public function __construct()
    {
        $this->serviceInstitutions = new ArrayCollection();
    }

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
     * Get the value of slug
     *
     * @return string|null
     */
    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * Set the value of slug
     *
     * @param string $slug
     *
     * @return $this
     */
    public function setSlug(string $slug): Service
    {
        $this->slug = $slug;

        return $this;
    }

    /**
     * Get the value of name
     *
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the value of name
     *
     * @param string $name
     *
     * @return $this
     */
    public function setName(string $name): Service
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the value of url
     *
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * Set the value of url
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url): Service
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Get the value of callback_path
     *
     * @return string|null
     */
    public function getCallbackPath(): ?string
    {
        return $this->callback_path;
    }

    /**
     * Set the value of callback_path
     *
     * @param string|null $callback_path
     *
     * @return $this
     */
    public function setCallbackPath(?string $callback_path): Service
    {
        $this->callback_path = $callback_path;

        return $this;
    }

    /**
     * Get the value of legacy_login_path
     *
     * @return string|null
     */
    public function getLegacyLoginPath(): ?string
    {
        return $this->legacy_login_path;
    }

    /**
     * Set the value of legacy_login_path
     *
     * @param string|null $legacy_login_path
     *
     * @return $this
     */
    public function setLegacyLoginPath(?string $legacy_login_path): Service
    {
        $this->legacy_login_path = $legacy_login_path;

        return $this;
    }

    /**
     * Get the value of serviceInstitutions
     *
     * @return Collection<int, InstitutionService>
     */
    public function getServiceInstitutions(): Collection
    {
        return $this->serviceInstitutions;
    }

    /**
     * Add a serviceInstitution
     *
     * @param InstitutionService $serviceInstitution
     *
     * @return $this
     */
    public function addServiceInstitution(InstitutionService $serviceInstitution): Service
    {
        if (!$this->serviceInstitutions->contains($serviceInstitution)) {
            $this->serviceInstitutions->add($serviceInstitution);
            $serviceInstitution->setService($this);
        }

        return $this;
    }

    /**
     * Remove a serviceInstitution
     *
     * @param InstitutionService $serviceInstitution
     *
     * @return $this
     */
    public function removeServiceInstitution(InstitutionService $serviceInstitution): Service
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
