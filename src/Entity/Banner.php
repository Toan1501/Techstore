<?php

namespace App\Entity;

use App\Repository\BannerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: BannerRepository::class)]
class Banner
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(length: 255)]
    private ?string $image = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $link = null;

    #[ORM\Column(options: ['default' => 0])]
    private ?int $position = 0;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $active = true;

    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): static { $this->title = $title; return $this; }

    public function getImage(): ?string { return $this->image; }
    public function setImage(string $image): static { $this->image = $image; return $this; }

    public function getLink(): ?string { return $this->link; }
    public function setLink(?string $link): static { $this->link = $link; return $this; }

    public function getPosition(): ?int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }

    public function isActive(): ?bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }
}
