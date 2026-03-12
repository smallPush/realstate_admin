<?php

namespace App\Domain\Apartment;

class Apartment
{
    private ?\DateTimeImmutable $vapiSyncedAt;

    public function __construct(
        string $name,
        string $address,
        int $price,
        bool $isAvailable = true,
        ?int $id = null,
        ?string $description = null,
        ?\DateTimeImmutable $vapiSyncedAt = null
    ) {
        $this->name = $name;
        $this->address = $address;
        $this->price = $price;
        $this->isAvailable = $isAvailable;
        $this->id = $id;
        $this->description = $description;
        $this->vapiSyncedAt = $vapiSyncedAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function setAddress(string $address): void
    {
        $this->address = $address;
    }

    public function isAvailable(): bool
    {
        return $this->isAvailable;
    }

    public function setIsAvailable(bool $isAvailable): void
    {
        $this->isAvailable = $isAvailable;
    }

    public function getPrice(): int
    {
        return $this->price;
    }

    public function setPrice(int $price): void
    {
        $this->price = $price;
    }

    public function getVapiSyncedAt(): ?\DateTimeImmutable
    {
        return $this->vapiSyncedAt;
    }

    public function setVapiSyncedAt(?\DateTimeImmutable $vapiSyncedAt): void
    {
        $this->vapiSyncedAt = $vapiSyncedAt;
    }
}
