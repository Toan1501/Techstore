<?php

namespace App\Service;

/**
 * Very simple shipping-fee policy for a Vietnam-based store.
 * - Free shipping when the goods subtotal reaches the threshold.
 * - Cheaper flat fee for the major central cities, higher for the rest.
 */
class ShippingCalculator
{
    public const FREE_SHIP_THRESHOLD = 5_000_000;
    public const FEE_MAJOR = 25_000;
    public const FEE_OTHER = 40_000;

    private const MAJOR_CITIES = [
        'Hà Nội', 'Hồ Chí Minh', 'Đà Nẵng', 'Hải Phòng', 'Cần Thơ',
    ];

    /** 63 provinces/cities of Vietnam. */
    public const PROVINCES = [
        'An Giang', 'Bà Rịa - Vũng Tàu', 'Bắc Giang', 'Bắc Kạn', 'Bạc Liêu', 'Bắc Ninh',
        'Bến Tre', 'Bình Định', 'Bình Dương', 'Bình Phước', 'Bình Thuận', 'Cà Mau',
        'Cần Thơ', 'Cao Bằng', 'Đà Nẵng', 'Đắk Lắk', 'Đắk Nông', 'Điện Biên', 'Đồng Nai',
        'Đồng Tháp', 'Gia Lai', 'Hà Giang', 'Hà Nam', 'Hà Nội', 'Hà Tĩnh', 'Hải Dương',
        'Hải Phòng', 'Hậu Giang', 'Hòa Bình', 'Hồ Chí Minh', 'Hưng Yên', 'Khánh Hòa',
        'Kiên Giang', 'Kon Tum', 'Lai Châu', 'Lâm Đồng', 'Lạng Sơn', 'Lào Cai', 'Long An',
        'Nam Định', 'Nghệ An', 'Ninh Bình', 'Ninh Thuận', 'Phú Thọ', 'Phú Yên', 'Quảng Bình',
        'Quảng Nam', 'Quảng Ngãi', 'Quảng Ninh', 'Quảng Trị', 'Sóc Trăng', 'Sơn La',
        'Tây Ninh', 'Thái Bình', 'Thái Nguyên', 'Thanh Hóa', 'Thừa Thiên Huế', 'Tiền Giang',
        'Trà Vinh', 'Tuyên Quang', 'Vĩnh Long', 'Vĩnh Phúc', 'Yên Bái',
    ];

    public function calculate(?string $province, float $subtotal): float
    {
        if ($subtotal >= self::FREE_SHIP_THRESHOLD) {
            return 0.0;
        }

        if ($province !== null && in_array($province, self::MAJOR_CITIES, true)) {
            return self::FEE_MAJOR;
        }

        return self::FEE_OTHER;
    }

    /** province => base fee (ignoring the free-ship threshold) — used for live JS preview. */
    public function feeMap(): array
    {
        $map = [];
        foreach (self::PROVINCES as $p) {
            $map[$p] = in_array($p, self::MAJOR_CITIES, true) ? self::FEE_MAJOR : self::FEE_OTHER;
        }

        return $map;
    }

    public function freeThreshold(): int
    {
        return self::FREE_SHIP_THRESHOLD;
    }
}
