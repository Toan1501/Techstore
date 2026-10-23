<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
class Coupon
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 40, unique: true)]
    private ?string $code = null;

    /** 'percent' or 'fixed' */
    #[ORM\Column(length: 10)]
    private ?string $type = 'percent';

    #[ORM\Column]
    private ?float $value = 0;

    #[ORM\Column(options: ['default' => 0])]
    private ?float $minOrder = 0;

    #[ORM\Column(options: ['default' => true])]
    private ?bool $active = true;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $expiresAt = null;

    /** Maximum number of times this coupon may be used (null = unlimited). */
    #[ORM\Column(nullable: true)]
    private ?int $maxUses = null;

    /** How many times this coupon has been used so far. */
    #[ORM\Column(options: ['default' => 0])]
    private int $usageCount = 0;

    public function getId(): ?int { return $this->id; }
    public function getCode(): ?string { return $this->code; }
    public function setCode(string $code): static { $this->code = strtoupper($code); return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }
    public function getValue(): ?float { return $this->value; }
    public function setValue(float $value): static { $this->value = $value; return $this; }
    public function getMinOrder(): ?float { return $this->minOrder; }
    public function setMinOrder(float $minOrder): static { $this->minOrder = $minOrder; return $this; }
    public function isActive(): ?bool { return $this->active; }
    public function setActive(bool $active): static { $this->active = $active; return $this; }
    public function getExpiresAt(): ?\DateTimeInterface { return $this->expiresAt; }
    public function setExpiresAt(?\DateTimeInterface $e): static { $this->expiresAt = $e; return $this; }
    public function getMaxUses(): ?int { return $this->maxUses; }
    public function setMaxUses(?int $maxUses): static { $this->maxUses = $maxUses; return $this; }
    public function getUsageCount(): int { return $this->usageCount; }
    public function setUsageCount(int $usageCount): static { $this->usageCount = $usageCount; return $this; }

    /** Whether the coupon still has uses left (always true when unlimited). */
    public function hasUsesLeft(): bool
    {
        return $this->maxUses === null || $this->usageCount < $this->maxUses;
    }

    public function isValidNow(): bool
    {
        return $this->active
            && ($this->expiresAt === null || $this->expiresAt >= new \DateTime())
            && $this->hasUsesLeft();
    }

    /** Discount amount for a given goods subtotal (0 if under minimum). */
    public function computeDiscount(float $subtotal): float
    {
        if ($subtotal < (float) $this->minOrder) {
            return 0.0;
        }
        $d = $this->type === 'fixed' ? (float) $this->value : $subtotal * (float) $this->value / 100;

        return min($d, $subtotal);
    }

    public function getLabel(): string
    {
        return $this->type === 'fixed'
            ? number_format((float) $this->value, 0, ',', '.') . ' ₫'
            : rtrim(rtrim((string) $this->value, '0'), '.') . '%';
    }
}
