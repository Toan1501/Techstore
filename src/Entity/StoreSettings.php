<?php

namespace App\Entity;

use App\Repository\StoreSettingsRepository;
use Doctrine\ORM\Mapping as ORM;

/** Single-row store configuration (edited in admin, read everywhere). */
#[ORM\Entity(repositoryClass: StoreSettingsRepository::class)]
class StoreSettings
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $storeName = 'TOÀN STORE';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $hotline = '1900 5301';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $email = 'support@toanstore.vn';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address = '123 Lê Lợi, Quận 1, TP. Hồ Chí Minh';

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $bankName = 'Vietcombank';

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $bankAccount = '0123456789';

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $bankOwner = 'TOÀN STORE';

    #[ORM\Column(options: ['default' => 5000000])]
    private ?int $freeShipThreshold = 5000000;

    #[ORM\Column(options: ['default' => 25000])]
    private ?int $shipFeeInner = 25000;

    #[ORM\Column(options: ['default' => 40000])]
    private ?int $shipFeeOuter = 40000;

    public function getId(): ?int { return $this->id; }
    public function getStoreName(): ?string { return $this->storeName; }
    public function setStoreName(string $v): static { $this->storeName = $v; return $this; }
    public function getHotline(): ?string { return $this->hotline; }
    public function setHotline(?string $v): static { $this->hotline = $v; return $this; }
    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $v): static { $this->email = $v; return $this; }
    public function getAddress(): ?string { return $this->address; }
    public function setAddress(?string $v): static { $this->address = $v; return $this; }
    public function getBankName(): ?string { return $this->bankName; }
    public function setBankName(?string $v): static { $this->bankName = $v; return $this; }
    public function getBankAccount(): ?string { return $this->bankAccount; }
    public function setBankAccount(?string $v): static { $this->bankAccount = $v; return $this; }
    public function getBankOwner(): ?string { return $this->bankOwner; }
    public function setBankOwner(?string $v): static { $this->bankOwner = $v; return $this; }
    public function getFreeShipThreshold(): ?int { return $this->freeShipThreshold; }
    public function setFreeShipThreshold(int $v): static { $this->freeShipThreshold = $v; return $this; }
    public function getShipFeeInner(): ?int { return $this->shipFeeInner; }
    public function setShipFeeInner(int $v): static { $this->shipFeeInner = $v; return $this; }
    public function getShipFeeOuter(): ?int { return $this->shipFeeOuter; }
    public function setShipFeeOuter(int $v): static { $this->shipFeeOuter = $v; return $this; }
}
