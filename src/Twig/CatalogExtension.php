<?php

namespace App\Twig;

use App\Entity\StoreSettings;
use App\Repository\CategoryRepository;
use App\Repository\StoreSettingsRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CatalogExtension extends AbstractExtension
{
    private ?StoreSettings $settings = null;

    public function __construct(
        private CategoryRepository $categoryRepository,
        private StoreSettingsRepository $settingsRepository,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('all_categories', [$this, 'allCategories']),
            new TwigFunction('store_settings', [$this, 'storeSettings']),
        ];
    }

    /** Categories for global navigation (menu / offcanvas). */
    public function allCategories(): array
    {
        return $this->categoryRepository->findAllOrdered();
    }

    /** Singleton store settings (memoized per request). */
    public function storeSettings(): StoreSettings
    {
        return $this->settings ??= $this->settingsRepository->get();
    }
}
