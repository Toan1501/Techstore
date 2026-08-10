<?php

namespace App\Tests\Cart;

use App\Cart\CartManager;
use PHPUnit\Framework\TestCase;

/** Lightweight product double — CartManager only needs getId() and getPrice(). */
class FakeProduct
{
    public function __construct(private int $id, private float $price)
    {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPrice(): float
    {
        return $this->price;
    }
}

class CartManagerTest extends TestCase
{
    public function testAddNewItem(): void
    {
        $cart = new CartManager();
        $cart->addItem(new FakeProduct(1, 100.0), 2);

        $this->assertCount(1, $cart->getItems());
        $this->assertSame(2, $cart->getTotalItems());
        $this->assertSame(200.0, $cart->getAmount());
    }

    public function testAddingSameProductIncrementsQuantity(): void
    {
        $product = new FakeProduct(1, 100.0);
        $cart = new CartManager();
        $cart->addItem($product, 1);
        $cart->addItem($product, 3);

        $this->assertCount(1, $cart->getItems());
        $this->assertSame(4, $cart->getTotalItems());
        $this->assertSame(400.0, $cart->getAmount());
    }

    public function testUpdateItemSetsQuantity(): void
    {
        $product = new FakeProduct(1, 50.0);
        $cart = new CartManager();
        $cart->addItem($product, 5);
        $cart->updateItem($product, 2);

        $this->assertSame(2, $cart->getTotalItems());
        $this->assertSame(100.0, $cart->getAmount());
    }

    public function testUpdateItemClampsNonPositiveQuantityToOne(): void
    {
        // Regression: a negative/zero quantity used to flow through to checkout,
        // bypassing the stock guard and producing a negative order total.
        $product = new FakeProduct(1, 50.0);
        $cart = new CartManager();
        $cart->addItem($product, 3);

        $cart->updateItem($product, -5);
        $this->assertSame(1, $cart->getTotalItems());
        $this->assertSame(50.0, $cart->getAmount());

        $cart->updateItem($product, 0);
        $this->assertSame(1, $cart->getTotalItems());
    }

    public function testRemoveItemByIdWorksWithoutProductEntity(): void
    {
        $product = new FakeProduct(7, 50.0);
        $cart = new CartManager();
        $cart->addItem($product, 1);
        $cart->removeItemById(7);

        $this->assertCount(0, $cart->getItems());
    }

    public function testRemoveItem(): void
    {
        $product = new FakeProduct(1, 50.0);
        $cart = new CartManager();
        $cart->addItem($product, 1);
        $cart->removeItem($product);

        $this->assertCount(0, $cart->getItems());
        $this->assertSame(0, $cart->getTotalItems());
        $this->assertSame(0, $cart->getAmount());
    }

    public function testAmountAcrossMultipleProducts(): void
    {
        $cart = new CartManager();
        $cart->addItem(new FakeProduct(1, 100.0), 2); // 200
        $cart->addItem(new FakeProduct(2, 250.0), 1); // 250

        $this->assertCount(2, $cart->getItems());
        $this->assertSame(3, $cart->getTotalItems());
        $this->assertSame(450.0, $cart->getAmount());
    }
}
