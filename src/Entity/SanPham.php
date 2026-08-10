<?php

namespace App\Entity;

use App\Repository\SanPhamRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SanPhamRepository::class)]
class SanPham
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 200)]
    #[Assert\NotBlank(message: 'Product name is required.')]
    #[Assert\Length(max: 200)]
    private ?string $name = null;

    #[ORM\Column]
    #[Assert\NotNull]
    #[Assert\Positive(message: 'Price must be greater than 0.')]
    private ?float $price = null;

    #[ORM\Column(nullable: true)]
    private ?float $priceOld = null;

    #[ORM\Column(length: 255)]
    private ?string $photo = null;

    #[ORM\ManyToOne(inversedBy: 'sanPhams')]
    private ?Category $cate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Stock cannot be negative.')]
    private ?int $stock = 0;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $sku = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $brand = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $warranty = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $specifications = null;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: OrderItem::class)]
    private Collection $orderItems;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductImage::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $images;

    public function __construct()
    {
        $this->orderItems = new ArrayCollection();
        $this->images = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(float $price): self
    {
        $this->price = $price;

        return $this;
    }

    public function getPriceOld(): ?float
    {
        return $this->priceOld;
    }

    public function setPriceOld(?float $priceOld): self
    {
        $this->priceOld = $priceOld;

        return $this;
    }

    /** True when there is a struck-through original price higher than the sale price. */
    public function hasDiscount(): bool
    {
        return $this->priceOld !== null && $this->priceOld > $this->price;
    }

    /** Discount percentage (e.g. 17) or 0 when there is no discount. */
    public function getDiscountPercent(): int
    {
        if (!$this->hasDiscount()) {
            return 0;
        }

        return (int) round((1 - $this->price / $this->priceOld) * 100);
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): self
    {
        $this->photo = $photo;

        return $this;
    }

    public function getCate(): ?Category
    {
        return $this->cate;
    }

    public function setCate(?Category $cate): static
    {
        $this->cate = $cate;

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

    public function getStock(): ?int
    {
        return $this->stock;
    }

    public function setStock(int $stock): static
    {
        $this->stock = $stock;

        return $this;
    }

    public function isInStock(): bool
    {
        return (int) $this->stock > 0;
    }

    public function getSku(): ?string
    {
        return $this->sku;
    }

    public function setSku(?string $sku): static
    {
        $this->sku = $sku;

        return $this;
    }

    public function getBrand(): ?string
    {
        return $this->brand;
    }

    public function setBrand(?string $brand): static
    {
        $this->brand = $brand;

        return $this;
    }

    public function getWarranty(): ?string
    {
        return $this->warranty;
    }

    public function setWarranty(?string $warranty): static
    {
        $this->warranty = $warranty;

        return $this;
    }

    public function getSpecifications(): ?string
    {
        return $this->specifications;
    }

    public function setSpecifications(?string $specifications): static
    {
        $this->specifications = $specifications;

        return $this;
    }

    /**
     * Parse the free-text specifications ("Key: Value" per line) into pairs.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public function getSpecList(): array
    {
        if (!$this->specifications) {
            return [];
        }

        $pairs = [];
        foreach (preg_split('/\r\n|\r|\n/', $this->specifications) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = explode(':', $line, 2);
            $pairs[] = [trim($parts[0]), trim($parts[1] ?? '')];
        }

        return $pairs;
    }

    /** @return array<string,string> spec name => value */
    public function getSpecMap(): array
    {
        $map = [];
        foreach ($this->getSpecList() as [$k, $v]) {
            $map[$k] = $v;
        }

        return $map;
    }

    /** @return Collection<int, ProductImage> */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(ProductImage $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setProduct($this);
        }

        return $this;
    }

    public function removeImage(ProductImage $image): static
    {
        if ($this->images->removeElement($image)) {
            if ($image->getProduct() === $this) {
                $image->setProduct(null);
            }
        }

        return $this;
    }

    /** All display images: main photo first, then gallery filenames. */
    public function getAllPhotos(): array
    {
        $photos = $this->photo ? [$this->photo] : [];
        foreach ($this->images as $img) {
            $photos[] = $img->getFilename();
        }

        return $photos;
    }

    // /**
    //  * @return Collection<int, OrderItem>
    //  */
    // public function getOrderItems(): Collection
    // {
    //     return $this->orderItems;
    // }

    // public function addOrderItem(OrderItem $orderItem): static
    // {
    //     if (!$this->orderItems->contains($orderItem)) {
    //         $this->orderItems->add($orderItem);
    //         $orderItem->setProduct($this);
    //     }

    //     return $this;
    // }

    // public function removeOrderItem(OrderItem $orderItem): static
    // {
    //     if ($this->orderItems->removeElement($orderItem)) {
    //         // set the owning side to null (unless already changed)
    //         if ($orderItem->getProduct() === $this) {
    //             $orderItem->setProduct(null);
    //         }
    //     }

    //     return $this;
    // }
}
