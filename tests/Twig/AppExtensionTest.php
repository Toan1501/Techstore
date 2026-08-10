<?php

namespace App\Tests\Twig;

use App\Twig\AppExtension;
use PHPUnit\Framework\TestCase;

class AppExtensionTest extends TestCase
{
    private AppExtension $ext;

    protected function setUp(): void
    {
        $this->ext = new AppExtension();
    }

    public function testFormatsIntegerAmount(): void
    {
        $this->assertSame('1.500.000 ₫', $this->ext->formatVnd(1500000));
    }

    public function testFormatsFloatByRounding(): void
    {
        $this->assertSame('1.500.001 ₫', $this->ext->formatVnd(1500000.5));
    }

    public function testFormatsZeroAndNull(): void
    {
        $this->assertSame('0 ₫', $this->ext->formatVnd(0));
        $this->assertSame('0 ₫', $this->ext->formatVnd(null));
    }

    public function testExposesVndFilter(): void
    {
        $names = array_map(fn ($f) => $f->getName(), $this->ext->getFilters());
        $this->assertContains('vnd', $names);
    }
}
