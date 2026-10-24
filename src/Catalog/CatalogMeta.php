<?php

namespace App\Catalog;

/**
 * Static catalogue presentation data: category icons, price buckets per category
 * and spec-key icons. Keyed by category name (the seed uses English names).
 */
final class CatalogMeta
{
    private const CATEGORY_ICONS = [
        'Laptop' => 'laptop',
        'Desktop PC' => 'pc-display',
        'Graphics Card' => 'gpu-card',
        'Monitor' => 'display',
        'Mouse' => 'mouse2',
        'RAM' => 'memory',
        'Keyboard' => 'keyboard',
    ];

    /** Vietnamese display labels for the English category names stored in the DB. */
    private const CATEGORY_LABELS = [
        'Laptop' => 'Laptop',
        'Desktop PC' => 'PC - Máy bộ',
        'Graphics Card' => 'Card đồ họa (VGA)',
        'Monitor' => 'Màn hình',
        'Mouse' => 'Chuột',
        'RAM' => 'RAM',
        'Keyboard' => 'Bàn phím',
    ];

    /** Price buckets in millions of VND: [min, max|null]. */
    private const PRICE_BUCKETS = [
        'Laptop' => [[0, 15], [15, 20], [20, 25], [25, 35], [35, null]],
        'Desktop PC' => [[0, 10], [10, 20], [20, 30], [30, 40], [40, null]],
        'Graphics Card' => [[0, 7], [7, 12], [12, 20], [20, 30], [30, null]],
        'Monitor' => [[0, 3], [3, 5], [5, 8], [8, null]],
        'Mouse' => [[0, 0.5], [0.5, 1], [1, 2], [2, null]],
        'RAM' => [[0, 1], [1, 2], [2, 3], [3, null]],
    ];
    private const DEFAULT_BUCKETS = [[0, 1], [1, 5], [5, 10], [10, 20], [20, null]];

    private const SPEC_ICONS = [
        'cpu' => 'cpu', 'chip' => 'cpu',
        'ram' => 'memory', 'vram' => 'memory', 'dung lượng' => 'memory',
        'storage' => 'device-ssd',
        'gpu' => 'gpu-card',
        'màn hình' => 'display', 'kích thước' => 'aspect-ratio',
        'tần số quét' => 'speedometer2', 'độ phân giải' => 'badge-hd', 'tấm nền' => 'layers',
        'thời gian phản hồi' => 'stopwatch', 'phản hồi' => 'stopwatch', 'tỷ lệ' => 'aspect-ratio',
        'dpi' => 'crosshair', 'cảm biến' => 'bullseye', 'số nút' => 'grid-3x2-gap',
        'kết nối' => 'wifi', 'trọng lượng' => 'feather', 'pin' => 'battery-full', 'polling' => 'activity',
        'bus' => 'lightning-charge', 'loại' => 'tag',
        'nguồn' => 'plug', 'nguồn đề xuất' => 'plug', 'boost clock' => 'speedometer',
        'tản nhiệt' => 'fan', 'hđh' => 'windows', 'case' => 'pc',
    ];

    /** Storefront display order (best sellers first); unknown categories keep their order at the end. */
    private const ORDER = ['Laptop', 'Desktop PC', 'Graphics Card', 'Monitor', 'Mouse', 'Keyboard', 'RAM'];

    /**
     * @template T of object
     * @param T[] $categories
     * @return T[]
     */
    public static function sort(array $categories): array
    {
        $rank = array_flip(self::ORDER);
        usort($categories, static fn ($a, $b) => ($rank[$a->getName()] ?? 99) <=> ($rank[$b->getName()] ?? 99));

        return $categories;
    }

    public static function icon(?string $category): string
    {
        return self::CATEGORY_ICONS[$category] ?? 'tag';
    }

    public static function label(?string $category): string
    {
        return self::CATEGORY_LABELS[$category] ?? (string) $category;
    }

    public static function specIcon(string $key): string
    {
        return self::SPEC_ICONS[mb_strtolower(trim($key))] ?? 'dot';
    }

    /**
     * @return list<array{label: string, min: ?int, max: ?int}> price ranges in VND
     */
    public static function priceRanges(?string $category): array
    {
        $out = [];
        foreach (self::PRICE_BUCKETS[$category] ?? self::DEFAULT_BUCKETS as [$min, $max]) {
            $out[] = [
                'label' => self::rangeLabel($min, $max),
                'min' => $min > 0 ? (int) round($min * 1_000_000) : null,
                'max' => $max !== null ? (int) round($max * 1_000_000) : null,
            ];
        }

        return $out;
    }

    private static function rangeLabel(float $min, ?float $max): string
    {
        $fmt = static fn (float $m): string => $m < 1
            ? (int) round($m * 1000) . 'K'
            : rtrim(rtrim(number_format($m, 1, ',', ''), '0'), ',') . ' triệu';

        if ($min <= 0) {
            return 'Dưới ' . $fmt($max);
        }
        if ($max === null) {
            return 'Trên ' . $fmt($min);
        }

        return 'Từ ' . $fmt($min) . ' - ' . $fmt($max);
    }
}
