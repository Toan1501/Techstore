<?php

namespace App\Tests\Catalog;

use App\Catalog\CatalogMeta;
use App\Catalog\ListingFilters;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class ListingFiltersTest extends TestCase
{
    public function testDefaults(): void
    {
        $f = ListingFilters::fromRequest(new Request());

        self::assertSame('newest', $f['sort']);
        self::assertSame([], $f['brand']);
        self::assertNull($f['min_price']);
        self::assertNull($f['max_price']);
        self::assertFalse($f['in_stock']);
        self::assertNull($f['min_rating']);
        self::assertFalse(ListingFilters::isActive($f));
    }

    public function testPriceRadioOverridesManualInputs(): void
    {
        $f = ListingFilters::fromRequest(new Request([
            'price' => '15000000-20000000', 'min_price' => '1', 'max_price' => '2',
            'brand' => ['Asus', '', 'MSI'], 'sort' => 'bogus', 'min_rating' => '9',
        ]));

        self::assertSame(15000000, $f['min_price']);
        self::assertSame(20000000, $f['max_price']);
        self::assertSame(['Asus', 'MSI'], $f['brand']);
        self::assertSame('newest', $f['sort'], 'unknown sort falls back');
        self::assertNull($f['min_rating'], 'rating above 5 is ignored');
        self::assertTrue(ListingFilters::isActive($f));
    }

    public function testOpenEndedRangeAndManualPrice(): void
    {
        $open = ListingFilters::fromRequest(new Request(['price' => '35000000-']));
        self::assertSame(35000000, $open['min_price']);
        self::assertNull($open['max_price']);

        $manual = ListingFilters::fromRequest(new Request(['min_price' => '500000', 'max_price' => 'abc']));
        self::assertSame(500000, $manual['min_price']);
        self::assertNull($manual['max_price']);
    }

    public function testPriceRangeLabels(): void
    {
        $laptop = CatalogMeta::priceRanges('Laptop');
        self::assertSame('Dưới 15 triệu', $laptop[0]['label']);
        self::assertNull($laptop[0]['min']);
        self::assertSame(15000000, $laptop[0]['max']);
        self::assertSame('Trên 35 triệu', end($laptop)['label']);

        $mouse = CatalogMeta::priceRanges('Mouse');
        self::assertSame('Dưới 500K', $mouse[0]['label']);
        self::assertSame('Từ 500K - 1 triệu', $mouse[1]['label']);
    }
}
