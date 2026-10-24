<?php

namespace App\Catalog;

use App\Repository\SanPhamRepository;
use Symfony\Component\HttpFoundation\Request;

/**
 * Reads the listing sidebar filters (brand[], price range, stock, rating, sort)
 * from the query string into the array SanPhamRepository::queryFiltered() expects.
 */
final class ListingFilters
{
    public const SORTS = ['newest', 'discount', 'price_asc', 'price_desc', 'name'];

    public static function fromRequest(Request $request): array
    {
        $sort = (string) $request->query->get('sort', 'newest');
        if (!in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        $brands = $request->query->all()['brand'] ?? [];
        $brands = array_values(array_filter(array_map('strval', (array) $brands), static fn ($b) => $b !== ''));

        $int = static function ($v): ?int {
            return is_numeric($v) && (int) $v >= 0 ? (int) $v : null;
        };

        $minRating = $int($request->query->get('min_rating'));
        $minPrice = $int($request->query->get('min_price'));
        $maxPrice = $int($request->query->get('max_price'));

        // Sidebar price radios send one "min-max" value (either side may be empty).
        $range = (string) $request->query->get('price', '');
        if (preg_match('/^(\d*)-(\d*)$/', $range, $m)) {
            $minPrice = $m[1] !== '' ? (int) $m[1] : null;
            $maxPrice = $m[2] !== '' ? (int) $m[2] : null;
        }

        return [
            'sort' => $sort,
            'brand' => $brands,
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'in_stock' => $request->query->getBoolean('in_stock'),
            'min_rating' => $minRating && $minRating <= 5 ? $minRating : null,
        ];
    }

    /** True when any user-facing filter (not just sort) is active. */
    public static function isActive(array $filters): bool
    {
        return $filters['brand'] || $filters['min_price'] !== null || $filters['max_price'] !== null
            || $filters['in_stock'] || $filters['min_rating'];
    }

    /** Facet data for the sidebar: brand counts and price buckets for a category (or all). */
    public static function facets(SanPhamRepository $repo, $category = null): array
    {
        return [
            'brands' => $repo->brandCountsFor($category),
            'ranges' => CatalogMeta::priceRanges($category?->getName()),
        ];
    }
}
