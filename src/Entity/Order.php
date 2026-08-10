<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OrderRepository::class)]
#[ORM\Table(name: '`order`')]
class Order
{
    public const STATUS_PENDING   = 'pending';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_SHIPPING  = 'shipping';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    /** All selectable order statuses => Bootstrap badge colour */
    public const STATUSES = [
        self::STATUS_PENDING   => 'warning',
        self::STATUS_CONFIRMED => 'info',
        self::STATUS_SHIPPING  => 'primary',
        self::STATUS_COMPLETED => 'success',
        self::STATUS_CANCELLED => 'danger',
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToMany(mappedBy: 'orderRef', targetEntity: OrderItem::class)]
    private Collection $items;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter your name.')]
    private ?string $customer_name = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Please enter your delivery address.')]
    private ?string $customer_address = null;

    #[ORM\Column(length: 12)]
    #[Assert\NotBlank(message: 'Please enter your phone number.')]
    #[Assert\Regex(pattern: '/^[0-9]{9,12}$/', message: 'Phone number must be 9–12 digits.')]
    private ?string $customer_phone = null;

    #[ORM\Column(length: 150, nullable: true)]
    #[Assert\NotBlank(message: 'Please enter your email.')]
    #[Assert\Email(message: 'Please enter a valid email address.')]
    private ?string $customer_email = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Please choose your province/city.')]
    private ?string $shipping_province = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Please enter your district.')]
    private ?string $shipping_district = null;

    #[ORM\Column(length: 100, nullable: true)]
    #[Assert\NotBlank(message: 'Please enter your ward.')]
    private ?string $shipping_ward = null;

    #[ORM\Column(nullable: true, options: ['default' => 0])]
    private ?float $shipping_fee = 0;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $payment_method = 'cod';

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(max: 500)]
    private ?string $note = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $couponCode = null;

    #[ORM\Column(nullable: true, options: ['default' => 0])]
    private ?float $discount = 0;

    #[ORM\Column]
    private ?float $total_price = null;

    #[ORM\ManyToOne]
    private ?User $user = null;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        foreach ($this->getItems() as $existingItem) {
            // The item already exists, update the quantity
            if ($existingItem->equals($item)) {
                $existingItem->setQuantity(
                    $existingItem->getQuantity() + $item->getQuantity()
                );
                return $this;
            }
        }
    
        $this->items[] = $item;
        $item->setOrderRef($this);
    
        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        if ($this->items->removeElement($item)) {
            // set the owning side to null (unless already changed)
            if ($item->getOrderRef() === $this) {
                $item->setOrderRef(null);
            }
        }

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** Bootstrap contextual colour for the current status (for badges). */
    public function getStatusColor(): string
    {
        return self::STATUSES[$this->status] ?? 'secondary';
    }

    /** Vietnamese label for the current status. */
    public function getStatusLabel(): string
    {
        return [
            self::STATUS_PENDING   => 'Chờ xử lý',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_SHIPPING  => 'Đang giao',
            self::STATUS_COMPLETED => 'Hoàn thành',
            self::STATUS_CANCELLED => 'Đã hủy',
        ][$this->status] ?? ($this->status ?? '');
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
    public function getTotal(): float
    {
        $total = 0;

        foreach ($this->getItems() as $item) {
            $total += $item->getTotal();
        }

        return $total;
    }

    public function getCustomerName(): ?string
    {
        return $this->customer_name;
    }

    public function setCustomerName(string $customer_name): static
    {
        $this->customer_name = $customer_name;

        return $this;
    }

    public function getCustomerAddress(): ?string
    {
        return $this->customer_address;
    }

    public function setCustomerAddress(string $customer_address): static
    {
        $this->customer_address = $customer_address;

        return $this;
    }

    public function getCustomerPhone(): ?string
    {
        return $this->customer_phone;
    }

    public function setCustomerPhone(string $customer_phone): static
    {
        $this->customer_phone = $customer_phone;

        return $this;
    }

    public function getTotalPrice(): ?float
    {
        return $this->total_price;
    }

    public function setTotalPrice(float $total_price): static
    {
        $this->total_price = $total_price;

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

    public function getCustomerEmail(): ?string
    {
        return $this->customer_email;
    }

    public function setCustomerEmail(?string $customer_email): static
    {
        $this->customer_email = $customer_email;

        return $this;
    }

    public function getShippingProvince(): ?string
    {
        return $this->shipping_province;
    }

    public function setShippingProvince(?string $v): static
    {
        $this->shipping_province = $v;

        return $this;
    }

    public function getShippingDistrict(): ?string
    {
        return $this->shipping_district;
    }

    public function setShippingDistrict(?string $v): static
    {
        $this->shipping_district = $v;

        return $this;
    }

    public function getShippingWard(): ?string
    {
        return $this->shipping_ward;
    }

    public function setShippingWard(?string $v): static
    {
        $this->shipping_ward = $v;

        return $this;
    }

    public function getShippingFee(): ?float
    {
        return $this->shipping_fee;
    }

    public function setShippingFee(?float $shipping_fee): static
    {
        $this->shipping_fee = $shipping_fee;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->payment_method;
    }

    public function setPaymentMethod(?string $payment_method): static
    {
        $this->payment_method = $payment_method;

        return $this;
    }

    public function getPaymentMethodLabel(): string
    {
        return match ($this->payment_method) {
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            default => 'Thanh toán khi nhận hàng (COD)',
        };
    }

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getCouponCode(): ?string
    {
        return $this->couponCode;
    }

    public function setCouponCode(?string $couponCode): static
    {
        $this->couponCode = $couponCode;

        return $this;
    }

    public function getDiscount(): ?float
    {
        return $this->discount;
    }

    public function setDiscount(?float $discount): static
    {
        $this->discount = $discount;

        return $this;
    }

    /** Goods subtotal = total + discount − shipping. */
    public function getSubtotal(): float
    {
        return (float) $this->total_price + (float) $this->discount - (float) $this->shipping_fee;
    }

    /** Human-readable full delivery address. */
    public function getFullAddress(): string
    {
        return implode(', ', array_filter([
            $this->customer_address,
            $this->shipping_ward,
            $this->shipping_district,
            $this->shipping_province,
        ]));
    }
}
