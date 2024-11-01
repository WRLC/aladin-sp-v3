<?php

/** @noinspection PhpUnused */

namespace App\Entity;

use App\Repository\InstitutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Class Institution
 *
 */

#[ORM\Entity(repositoryClass: InstitutionRepository::class)]
#[UniqueEntity(fields: 'instIndex', message: 'This Institution index is already in use.')]
#[UniqueEntity(fields: 'name', message: 'This Institution name is already in use.')]
class Institution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $instIndex = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wayfLabel = null;

    #[Gedmo\SortablePosition]
    #[ORM\Column]
    private ?int $position = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $entityId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $almaLocCode = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $mailAttribute = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $nameAttribute = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstNameAttr = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $idAttribute = null;

    #[ORM\Column(nullable: true)]
    private ?bool $specialTransform = false;

    /** @var Collection<int, InstitutionService> */
    #[ORM\OneToMany(targetEntity: InstitutionService::class, mappedBy: 'institution')]
    private Collection $institutionServices;

    /**
     * Constructor for the Institution entity.
     */
    public function __construct()
    {
        $this->institutionServices = new ArrayCollection();
    }

    /**
     * Get the Institution ID (primary key).
     *
     * @return int|null The Institution ID.
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * Get the Institution index.
     *
     * @return string|null The Institution index.
     */
    public function getIndex(): ?string
    {
        return $this->instIndex;
    }

    /**
     * Set the Institution index.
     *
     * @param string $index The Institution index.
     *
     * @return $this The Institution entity.
     */
    public function setIndex(string $index): Institution
    {
        $this->instIndex = $index;

        return $this;
    }

    /**
     * Get the Institution name.
     *
     * @return string|null The Institution name.
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * Set the Institution name.
     *
     * @param string $name The Institution name.
     *
     * @return $this The Institution entity.
     */
    public function setName(string $name): Institution
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the Institution WAYF label.
     *
     * @return string|null The Institution WAYF label.
     */
    public function getWayfLabel(): ?string
    {
        return $this->wayfLabel;
    }

    /**
     * Set the Institution WAYF label.
     *
     * @param string|null $wayfLabel The Institution WAYF label.
     *
     * @return $this The Institution entity.
     */
    public function setWayfLabel(?string $wayfLabel): Institution
    {
        $this->wayfLabel = $wayfLabel;

        return $this;
    }

    /**
     * Get the Institution position.
     *
     * @return int|null The Institution position.
     */
    public function getPosition(): ?int
    {
        return $this->position;
    }

    /**
     * Set the Institution position.
     *
     * @param int $position The Institution position.
     *
     * @return $this The Institution entity.
     */
    public function setPosition(int $position): Institution
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Get the Institution entity ID.
     *
     * @return string|null The Institution entity ID.
     */
    public function getEntityId(): ?string
    {
        return $this->entityId;
    }

    /**
     * Set the Institution entity ID.
     *
     * @param string $entityId The Institution entity ID.
     *
     * @return $this The Institution entity.
     */
    public function setEntityId(string $entityId): Institution
    {
        $this->entityId = $entityId;

        return $this;
    }

    /**
     * Get the Institution Alma location code.
     *
     * @return string|null The Institution Alma location code.
     */
    public function getAlmaLocationCode(): ?string
    {
        return $this->almaLocCode;
    }

    /**
     * Set the Institution Alma location code.
     *
     * @param string|null $almaLocCode The Institution Alma location code.
     *
     * @return $this The Institution entity.
     */
    public function setAlmaLocationCode(?string $almaLocCode): Institution
    {
        $this->almaLocCode = $almaLocCode;

        return $this;
    }

    /**
     * Get the Institution mail attribute.
     *
     * @return string|null The Institution mail attribute.
     */
    public function getMailAttribute(): ?string
    {
        return $this->mailAttribute;
    }

    /**
     * Set the Institution mail attribute.
     *
     * @param string $mailAttribute The Institution mail attribute.
     *
     * @return $this The Institution entity.
     */
    public function setMailAttribute(string $mailAttribute): Institution
    {
        $this->mailAttribute = $mailAttribute;

        return $this;
    }

    /**
     * Get the Institution name attribute.
     *
     * @return string|null The Institution name attribute.
     */
    public function getNameAttribute(): ?string
    {
        return $this->nameAttribute;
    }

    /**
     * Set the Institution name attribute.
     *
     * @param string|null $nameAttribute The Institution name attribute.
     *
     * @return $this The Institution entity.
     */
    public function setNameAttribute(?string $nameAttribute): Institution
    {
        $this->nameAttribute = $nameAttribute;

        return $this;
    }

    /**
     * Get the Institution first name attribute.
     *
     * @return string|null The Institution first name attribute.
     */
    public function getFirstNameAttribute(): ?string
    {
        return $this->firstNameAttr;
    }

    /**
     * Set the Institution first name attribute.
     *
     * @param string|null $firstNameAttr The Institution first name attribute.
     *
     * @return $this The Institution entity.
     */
    public function setFirstNameAttribute(?string $firstNameAttr): Institution
    {
        $this->firstNameAttr = $firstNameAttr;

        return $this;
    }

    /**
     * Get the Institution user ID attribute.
     *
     * @return string|null The Institution ID attribute.
     */
    public function getIdAttribute(): ?string
    {
        return $this->idAttribute;
    }

    /**
     * Set the Institution user ID attribute.
     *
     * @param string|null $idAttribute The Institution ID attribute.
     *
     * @return $this The Institution entity.
     */
    public function setIdAttribute(?string $idAttribute): Institution
    {
        $this->idAttribute = $idAttribute;

        return $this;
    }

    /**
     * Get the Special Transform toggler state
     *
     * @return bool|null The Special Transform toggler state
     */
    public function isSpecialTransform(): ?bool
    {
        if ($this->specialTransform) {
            return true;
        }
        return false;
    }

    /**
     * Set the Special Transform toggler state
     *
     *
     * @return $this The Institution entity
     */
    public function setSpecialTransform(): Institution
    {
        $this->specialTransform = true;

        return $this;
    }

    /**
     * Unset the Special Transform toggler state
     *
     * @return $this The Institution entity
     */
    public function unsetSpecialTransform(): Institution
    {
        $this->specialTransform = false;

        return $this;
    }

    /**
     * Get the Institution services linked to this Institution.
     *
     * @return Collection<int, InstitutionService>
     */
    public function getInstitutionServices(): Collection
    {
        return $this->institutionServices;
    }

    /**
     * Add an Institution service to this Institution.
     *
     * @param InstitutionService $institutionService The Institution service to add.
     *
     * @return $this The Institution entity.
     */
    public function addInstitutionService(InstitutionService $institutionService): Institution
    {
        if (!$this->institutionServices->contains($institutionService)) {
            $this->institutionServices->add($institutionService);
            $institutionService->setInstitution($this);
        }

        return $this;
    }

    /**
     * Remove an Institution service from this Institution.
     *
     * @param InstitutionService $institutionService The Institution service to remove.
     *
     * @return $this The Institution entity.
     */
    public function removeInstitutionService(InstitutionService $institutionService): Institution
    {
        if ($this->institutionServices->removeElement($institutionService)) {
            // set the owning side to null (unless already changed)
            if ($institutionService->getInstitution() === $this) {
                $institutionService->setInstitution(null);
            }
        }

        return $this;
    }
}
