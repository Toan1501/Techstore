<?php

namespace App\Entity;

use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?SanPham $product = null;

    /** Null for imported/demo reviews, which carry authorName instead. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?User $user = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $authorName = null;

    /** Sample data for the demo store: removable in one go with `app:demo-reviews --purge`. */
    #[ORM\Column(options: ['default' => false])]
    private bool $demo = false;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 5, notInRangeMessage: 'Rating must be between {{ min }} and {{ max }}.')]
    private ?int $rating = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 2000)]
    private ?string $comment = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

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

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAuthorName(): ?string
    {
        return $this->authorName;
    }

    public function setAuthorName(?string $authorName): static
    {
        $this->authorName = $authorName;

        return $this;
    }

    public function isDemo(): bool
    {
        return $this->demo;
    }

    public function setDemo(bool $demo): static
    {
        $this->demo = $demo;

        return $this;
    }

    /** Name shown on the product page. */
    public function getDisplayName(): string
    {
        if ($this->user) {
            return trim(($this->user->getFirstName() ?? '') . ' ' . ($this->user->getLastName() ? mb_substr($this->user->getLastName(), 0, 1) . '.' : '')) ?: (string) $this->user->getUserIdentifier();
        }

        return $this->authorName ?: 'Khách hàng';
    }
}
