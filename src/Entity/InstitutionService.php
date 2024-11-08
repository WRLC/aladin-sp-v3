<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\InstitutionServiceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * Class InstitutionService
 *
 * @SuppressWarnings(PHPMD.ShortVariable)
 */
#[ORM\Entity(repositoryClass: InstitutionServiceRepository::class)]
#[UniqueEntity(
    fields: ['institution', 'service'],
    message: 'This service is already associated with the institution.'
)]
class InstitutionService
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Institution::class, inversedBy: 'institutionServices')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Institution $institution = null;

    #[ORM\ManyToOne(targetEntity: Service::class, inversedBy: 'serviceInstitutions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Service $service = null;

    #[ORM\Column(length: 255)]
    private ?string $authzType = null;

    #[ORM\Column(length: 255)]
    private ?string $idAttribute = null;

    /**
     * @var Collection<int, AuthzMember>
     */
    #[ORM\OneToMany(targetEntity: AuthzMember::class, mappedBy: 'institutionService', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $authzMembers;

    public function __construct()
    {
        $this->authzMembers = new ArrayCollection();
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
     * Get the value of Institution
     *
     * @return Institution|null
     */
    public function getInstitution(): ?Institution
    {
        return $this->institution;
    }

    /**
     * Set the value of Institution
     *
     * @param Institution|null $institution
     *
     * @return $this
     */
    public function setInstitution(?Institution $institution): InstitutionService
    {
        $this->institution = $institution;

        return $this;
    }

    /**
     * Get the value of Service
     *
     * @return Service|null
     */
    public function getService(): ?Service
    {
        return $this->service;
    }

    /**
     * Set the value of Service
     *
     * @param Service|null $service
     *
     * @return $this
     */
    public function setService(?Service $service): InstitutionService
    {
        $this->service = $service;

        return $this;
    }

    /**
     * Get the value of AuthzType
     *
     * @return string|null
     */
    public function getAuthzType(): ?string
    {
        return $this->authzType;
    }

    /**
     * Set the value of AuthzType
     *
     * @param string|null $authzType
     *
     * @return $this
     */
    public function setAuthzType(?string $authzType): InstitutionService
    {
        $this->authzType = $authzType;

        return $this;
    }

    /**
     * Get the value of id_attribute
     *
     * @return string|null
     */
    public function getIdAttribute(): ?string
    {
        return $this->idAttribute;
    }

    /**
     * Set the value of id_attribute
     *
     * @param string $idAttribute
     *
     * @return $this
     */
    public function setIdAttribute(string $idAttribute): InstitutionService
    {
        $this->idAttribute = $idAttribute;

        return $this;
    }

    /**
     * Get the value of authzMembers
     *
     * @return Collection<int, AuthzMember>
     */
    public function getAuthzMembers(): Collection
    {
        return $this->authzMembers;
    }

    /**
     * Add an AuthzMember to the collection
     *
     * @param AuthzMember $authzMember
     *
     * @return $this
     */
    public function addAuthzMember(AuthzMember $authzMember): InstitutionService
    {
        if (!$this->authzMembers->contains($authzMember)) {
            $this->authzMembers->add($authzMember);
            $authzMember->setInstitutionService($this);
        }

        return $this;
    }

    /**
     * Remove an AuthzMember from the collection
     *
     * @param AuthzMember $authzMember
     *
     * @return $this
     */
    public function removeAuthzMember(AuthzMember $authzMember): InstitutionService
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
