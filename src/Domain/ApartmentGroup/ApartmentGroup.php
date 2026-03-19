<?php

namespace App\Domain\ApartmentGroup;

class ApartmentGroup
{
    private ?int $id;
    private string $name;
    private ?ApartmentGroup $parent;

    public function __construct(string $name, ?ApartmentGroup $parent = null, ?int $id = null)
    {
        $this->name = $name;
        $this->parent = $parent;
        $this->id = $id;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getParent(): ?ApartmentGroup
    {
        return $this->parent;
    }
}
