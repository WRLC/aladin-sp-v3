<?php

namespace App\Entity;

use App\Repository\InstitutionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This class defines an Institution entity.
 *
 * An Institution (Identity Provider) is a service that provides authentication to users. SSP stores information about Institutions in PHP files in the metadata directory. This class represents an Institution entity in the database linked to one of SSP's Institutions.
 */

#[ORM\Entity(repositoryClass: InstitutionRepository::class)]
#[UniqueEntity(fields: 'inst_index', message: 'This Institution index is already in use.')]
#[UniqueEntity(fields: 'name', message: 'This Institution name is already in use.')]
class Institution
{
    /**
     * The Institution ID (primary key).
     */
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * The Institution index (the short form for use in the wayf menu).
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $inst_index = null;

    /**
     * The Institution name.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $name = null;

    /**
     * WAYF label.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $wayf_label = null;

    /**
     * The sorting order of the Institution in the wayf menu and other lists.
     */
    #[Gedmo\SortablePosition]
    #[ORM\Column(type: 'integer')]
    private ?int $position = null;

    /**
     * The Institution entity ID, which must match an Institution entity ID in the metadata directory.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $entity_id = null;

    /**
     * The Institution Alma location code.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $alma_location_code = null;

    /**
     * The Institution user email attribute.
     */
    #[ORM\Column(length: 255)]
    #[Assert\NotNull]
    private ?string $mail_attribute = null;

    /**
     * The Institution user (last) name attribute.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name_attribute = null;

    /**
     * The Institution user first name attribute.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $first_name_attribute = null;

    /**
     * The Institution user ID attribute.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $id_attribute = null;

    /**
     * The Institution services linked to this Institution.
     *
     * @var Collection<int, InstitutionService>
     */
    #[ORM\OneToMany(targetEntity: InstitutionService::class, mappedBy: 'Institution')]
    private Collection $InstitutionServices;

    /**
     * Constructor for the Institution entity.
     */
    public function __construct()
    {
        $this->InstitutionServices = new ArrayCollection();
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
        return $this->inst_index;
    }

    /**
     * Set the Institution index.
     *
     * @param string $index The Institution index.
     * @return Institution The Institution entity.
     */
    public function setIndex(string $index): static
    {
        $this->inst_index = $index;

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
     * @return Institution The Institution entity.
     */
    public function setName(string $name): static
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
        return $this->wayf_label;
    }

    /**
     * Set the Institution WAYF label.
     *
     * @param string|null $wayf_label The Institution WAYF label.
     * @return Institution The Institution entity.
     */
    public function setWayfLabel(?string $wayf_label): static
    {
        $this->wayf_label = $wayf_label;

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
     * @return Institution The Institution entity.
     */
    public function setPosition(int $position): static
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
        return $this->entity_id;
    }

    /**
     * Set the Institution entity ID.
     *
     * @param string $entity_id The Institution entity ID.
     * @return Institution The Institution entity.
     */
    public function setEntityId(string $entity_id): static
    {
        $this->entity_id = $entity_id;

        return $this;
    }

    /**
     * Get the Institution Alma location code.
     *
     * @return string|null The Institution Alma location code.
     */
    public function getAlmaLocationCode(): ?string
    {
        return $this->alma_location_code;
    }

    /**
     * Set the Institution Alma location code.
     *
     * @param string|null $alma_location_code The Institution Alma location code.
     * @return Institution The Institution entity.
     */
    public function setAlmaLocationCode(?string $alma_location_code): static
    {
        $this->alma_location_code = $alma_location_code;

        return $this;
    }

    /**
     * Get the Institution mail attribute.
     *
     * @return string|null The Institution mail attribute.
     */
    public function getMailAttribute(): ?string
    {
        return $this->mail_attribute;
    }

    /**
     * Set the Institution mail attribute.
     *
     * @param string $mail_attribute The Institution mail attribute.
     * @return Institution The Institution entity.
     */
    public function setMailAttribute(string $mail_attribute): static
    {
        $this->mail_attribute = $mail_attribute;

        return $this;
    }

    /**
     * Get the Institution name attribute.
     *
     * @return string|null The Institution name attribute.
     */
    public function getNameAttribute(): ?string
    {
        return $this->name_attribute;
    }

    /**
     * Set the Institution name attribute.
     *
     * @param string|null $name_attribute The Institution name attribute.
     * @return Institution The Institution entity.
     */
    public function setNameAttribute(?string $name_attribute): static
    {
        $this->name_attribute = $name_attribute;

        return $this;
    }

    /**
     * Get the Institution first name attribute.
     *
     * @return string|null The Institution first name attribute.
     */
    public function getFirstNameAttribute(): ?string
    {
        return $this->first_name_attribute;
    }

    /**
     * Set the Institution first name attribute.
     *
     * @param string|null $first_name_attribute The Institution first name attribute.
     * @return Institution The Institution entity.
     */
    public function setFirstNameAttribute(?string $first_name_attribute): static
    {
        $this->first_name_attribute = $first_name_attribute;

        return $this;
    }

    /**
     * Get the Institution user ID attribute.
     *
     * @return string|null The Institution ID attribute.
     */
    public function getIdAttribute(): ?string
    {
        return $this->id_attribute;
    }

    /**
     * Set the Institution user ID attribute.
     *
     * @param string|null $id_attribute The Institution ID attribute.
     * @return Institution The Institution entity.
     */
    public function setIdAttribute(?string $id_attribute): static
    {
        $this->id_attribute = $id_attribute;

        return $this;
    }

    /**
     * Get the Institution services linked to this Institution.
     *
     * @return Collection<int, InstitutionService>
     */
    public function getInstitutionServices(): Collection
    {
        return $this->InstitutionServices;
    }

    /**
     * Add an Institution service to this Institution.
     *
     * @param InstitutionService $InstitutionService The Institution service to add.
     * @return Institution The Institution entity.
     */
    public function addInstitutionService(InstitutionService $InstitutionService): static
    {
        if (!$this->InstitutionServices->contains($InstitutionService)) {
            $this->InstitutionServices->add($InstitutionService);
            $InstitutionService->setInstitution($this);
        }

        return $this;
    }

    /**
     * Remove an Institution service from this Institution.
     *
     * @param InstitutionService $InstitutionService The Institution service to remove.
     * @return Institution The Institution entity.
     */
    public function removeInstitutionService(InstitutionService $InstitutionService): static
    {
        if ($this->InstitutionServices->removeElement($InstitutionService)) {
            // set the owning side to null (unless already changed)
            if ($InstitutionService->getInstitution() === $this) {
                $InstitutionService->setInstitution(null);
            }
        }

        return $this;
    }
}
