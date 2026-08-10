<?php

namespace App\Twig;

use App\Repository\CategoryRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class CatalogExtension extends AbstractExtension
{
    public function __construct(private CategoryRepository $categoryRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('all_categories', [$this, 'allCategories']),
        ];
    }

    /** Categories for global navigation (menu / offcanvas). */
    public function allCategories(): array
    {
        return $this->categoryRepository->findAllOrdered();
    }
}
