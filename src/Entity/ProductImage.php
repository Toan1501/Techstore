<?php

namespace App\Entity;

use App\Repository\ProductImageRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductImageRepository::class)]
class ProductImage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'images')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SanPham $product = null;

    #[ORM\Column(length: 255)]
    private ?string $filename = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProduct(): ?SanPham
    {
        return $this->product;
    }

    public function setProduct(?SanPham $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getFilename(): ?string
    {
        return $this->filename;
    }

    public function setFilename(string $filename): static
    {
        $this->filename = $filename;

        return $this;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }
}
