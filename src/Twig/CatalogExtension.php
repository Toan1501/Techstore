<?php

namespace App\Twig;

use App\Catalog\CatalogMeta;
use App\Entity\SanPham;
use App\Entity\StoreSettings;
use App\Repository\CategoryRepository;
use App\Repository\SanPhamRepository;
use App\Repository\StoreSettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class CatalogExtension extends AbstractExtension
{
    private ?StoreSettings $settings = null;
    private ?array $categories = null;
    private ?array $megaMenu = null;

    public function __construct(
        private CategoryRepository $categoryRepository,
        private StoreSettingsRepository $settingsRepository,
        private SanPhamRepository $productRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('all_categories', [$this, 'allCategories']),
            new TwigFunction('store_settings', [$this, 'storeSettings']),
            new TwigFunction('mega_menu', [$this, 'megaMenu']),
            new TwigFunction('price_ranges', [CatalogMeta::class, 'priceRanges']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('cat_icon', [CatalogMeta::class, 'icon']),
            new TwigFilter('cat_label', [CatalogMeta::class, 'label']),
            new TwigFilter('spec_icon', [CatalogMeta::class, 'specIcon']),
            new TwigFilter('spec_chips', [$this, 'specChips']),
        ];
    }

    /** Categories for global navigation (menu / offcanvas), memoized per request. */
    public function allCategories(): array
    {
        return $this->categories ??= CatalogMeta::sort($this->categoryRepository->findAllOrdered());
    }

    /** Singleton store settings (memoized per request). */
    public function storeSettings(): StoreSettings
    {
        return $this->settings ??= $this->settingsRepository->get();
    }

    /**
     * Data for the "Danh mục" mega menu: each category with its brands and price buckets.
     *
     * @return list<array{category: object, icon: string, label: string, brands: array<string,int>, ranges: array}>
     */
    public function megaMenu(): array
    {
        if ($this->megaMenu !== null) {
            return $this->megaMenu;
        }

        $brands = $this->productRepository->brandCountsByCategory();
        $menu = [];
        foreach ($this->allCategories() as $c) {
            $menu[] = [
                'category' => $c,
                'icon' => CatalogMeta::icon($c->getName()),
                'label' => CatalogMeta::label($c->getName()),
                'brands' => array_slice($brands[$c->getId()] ?? [], 0, 8, true),
                'ranges' => CatalogMeta::priceRanges($c->getName()),
            ];
        }

        return $this->megaMenu = $menu;
    }

    /**
     * Up to $limit [icon, value] pairs for the spec box on product cards.
     *
     * @return list<array{0: string, 1: string}>
     */
    public function specChips(SanPham $product, int $limit = 4): array
    {
        $out = [];
        foreach ($product->getSpecList() as [$key, $value]) {
            if ($value === '' || mb_strtolower($key) === 'hđh') {
                continue;
            }
            $out[] = [CatalogMeta::specIcon($key), $value];
            if (count($out) >= $limit) {
                break;
            }
        }

        return $out;
    }
}
