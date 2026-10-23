<?php

namespace App\Tests\Entity;

use App\Entity\Coupon;
use PHPUnit\Framework\TestCase;

class CouponTest extends TestCase
{
    private function coupon(): Coupon
    {
        return (new Coupon())->setCode('TEST')->setType('percent')->setValue(10)->setActive(true);
    }

    public function testUnlimitedCouponAlwaysHasUsesLeft(): void
    {
        $c = $this->coupon(); // maxUses null by default
        $this->assertNull($c->getMaxUses());
        $this->assertTrue($c->hasUsesLeft());
        $c->setUsageCount(9999);
        $this->assertTrue($c->hasUsesLeft());
    }

    public function testLimitedCouponRunsOut(): void
    {
        $c = $this->coupon()->setMaxUses(3);
        $c->setUsageCount(2);
        $this->assertTrue($c->hasUsesLeft());
        $this->assertTrue($c->isValidNow());

        $c->setUsageCount(3);
        $this->assertFalse($c->hasUsesLeft());
        $this->assertFalse($c->isValidNow(), 'An exhausted coupon must be invalid everywhere');
    }

    public function testExpiredCouponIsInvalid(): void
    {
        $c = $this->coupon()->setExpiresAt(new \DateTime('-1 day'));
        $this->assertFalse($c->isValidNow());
    }

    public function testInactiveCouponIsInvalid(): void
    {
        $c = $this->coupon()->setActive(false);
        $this->assertFalse($c->isValidNow());
    }

    public function testComputeDiscountRespectsMinOrder(): void
    {
        $c = $this->coupon()->setValue(10)->setMinOrder(1000000);
        $this->assertSame(0.0, $c->computeDiscount(500000));
        $this->assertSame(100000.0, $c->computeDiscount(1000000));
    }
}
