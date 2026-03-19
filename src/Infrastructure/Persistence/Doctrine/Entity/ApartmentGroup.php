<?php

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Infrastructure\Persistence\Doctrine\Repository\ApartmentGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApartmentGroupRepository::class)]
class ApartmentGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?self $parent = null;

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children;

    #[ORM\ManyToMany(targetEntity: User::class, mappedBy: 'apartmentGroups')]
    private Collection $users;

    #[ORM\ManyToMany(targetEntity: Apartment::class, mappedBy: 'apartmentGroups')]
    private Collection $apartments;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->users = new ArrayCollection();
        $this->apartments = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): static
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection
    {
        return $this->children;
    }

    public function addChild(self $child): static
    {
        if (!$this->children->contains($child)) {
            $this->children->add($child);
            $child->setParent($this);
        }

        return $this;
    }

    public function removeChild(self $child): static
    {
        if ($this->children->removeElement($child)) {
            // set the owning side to null (unless already changed)
            if ($child->getParent() === $this) {
                $child->setParent(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->addApartmentGroup($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            $user->removeApartmentGroup($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Apartment>
     */
    public function getApartments(): Collection
    {
        return $this->apartments;
    }

    public function addApartment(Apartment $apartment): static
    {
        if (!$this->apartments->contains($apartment)) {
            $this->apartments->add($apartment);
            $apartment->addApartmentGroup($this);
        }

        return $this;
    }

    public function removeApartment(Apartment $apartment): static
    {
        if ($this->apartments->removeElement($apartment)) {
            $apartment->removeApartmentGroup($this);
        }

        return $this;
    }
}
