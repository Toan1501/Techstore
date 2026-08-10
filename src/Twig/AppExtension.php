<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('vnd', [$this, 'formatVnd']),
        ];
    }

    /**
     * Format a numeric amount as Vietnamese Dong, e.g. 1500000 -> "1.500.000 ₫".
     */
    public function formatVnd(int|float|string|null $amount): string
    {
        $amount = (float) ($amount ?? 0);

        return number_format($amount, 0, ',', '.') . ' ₫';
    }
}
