<?php

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Infrastructure\Persistence\Doctrine\Repository\ApartmentRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ApartmentRepository::class)]
class Apartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    private ?string $address = null;

    #[ORM\Column]
    private ?bool $isAvailable = null;

    #[ORM\Column]
    private ?int $price = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $vapiSyncedAt = null;

    #[ORM\ManyToMany(targetEntity: ApartmentGroup::class, inversedBy: 'apartments')]
    #[ORM\JoinTable(name: 'apartment_apartment_group')]
    private \Doctrine\Common\Collections\Collection $apartmentGroups;

    public function __construct()
    {
        $this->apartmentGroups = new \Doctrine\Common\Collections\ArrayCollection();
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;

        return $this;
    }

    public function isAvailable(): ?bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): static
    {
        $this->isAvailable = $isAvailable;

        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function setPrice(int $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getVapiSyncedAt(): ?\DateTimeImmutable
    {
        return $this->vapiSyncedAt;
    }

    public function setVapiSyncedAt(?\DateTimeImmutable $vapiSyncedAt): static
    {
        $this->vapiSyncedAt = $vapiSyncedAt;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection<int, ApartmentGroup>
     */
    public function getApartmentGroups(): \Doctrine\Common\Collections\Collection
    {
        return $this->apartmentGroups;
    }

    public function addApartmentGroup(ApartmentGroup $apartmentGroup): static
    {
        if (!$this->apartmentGroups->contains($apartmentGroup)) {
            $this->apartmentGroups->add($apartmentGroup);
        }

        return $this;
    }

    public function removeApartmentGroup(ApartmentGroup $apartmentGroup): static
    {
        $this->apartmentGroups->removeElement($apartmentGroup);

        return $this;
    }
}
