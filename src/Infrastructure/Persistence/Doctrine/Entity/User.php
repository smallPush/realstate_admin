<?php

namespace App\Infrastructure\Persistence\Doctrine\Entity;

use App\Infrastructure\Persistence\Doctrine\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string|null The hashed password
     */
    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\ManyToMany(targetEntity: ApartmentGroup::class, inversedBy: 'users')]
    #[ORM\JoinTable(name: 'user_apartment_group')]
    private \Doctrine\Common\Collections\Collection $apartmentGroups;

    public function __construct()
    {
        $this->apartmentGroups = new \Doctrine\Common\Collections\ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
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
