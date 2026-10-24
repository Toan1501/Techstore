<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\NewsRepository;
use App\Repository\SanPhamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    /** robots.txt is generated so the Sitemap directive is an absolute URL (required by the spec). */
    #[Route('/robots.txt', name: 'app_robots', defaults: ['_format' => 'txt'])]
    public function robots(): Response
    {
        $body = "User-agent: *\n"
            . "Allow: /\n"
            . "Disallow: /admin\n"
            . "Disallow: /login\n"
            . "Disallow: /register\n"
            . "Disallow: /order\n"
            . "Disallow: /cart\n"
            . "Disallow: /my-orders\n"
            . "Disallow: /profile\n"
            . "Disallow: /reset-password\n"
            . "Disallow: /payment\n"
            . "\nSitemap: " . $this->generateUrl('app_sitemap', [], UrlGeneratorInterface::ABSOLUTE_URL) . "\n";

        return new Response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    #[Route('/sitemap.xml', name: 'app_sitemap', defaults: ['_format' => 'xml'])]
    public function index(SanPhamRepository $products, CategoryRepository $categories, NewsRepository $news): Response
    {
        $urls = [];
        $abs = UrlGeneratorInterface::ABSOLUTE_URL;

        $urls[] = $this->generateUrl('homepage', [], $abs);
        $urls[] = $this->generateUrl('feature_product', [], $abs);
        $urls[] = $this->generateUrl('app_category', [], $abs);
        $urls[] = $this->generateUrl('app_news', [], $abs);
        $urls[] = $this->generateUrl('app_contact', [], $abs);

        // Static SEO content pages (about, policies, guides, FAQ).
        foreach (PageController::pages() as $p) {
            $urls[] = $this->generateUrl($p['route'], [], $abs);
        }
        $urls[] = $this->generateUrl('page_faq', [], $abs);

        // Discovery pages, lookups, need-based landing pages and brand pages.
        foreach (['shop_promotions', 'shop_brands', 'shop_stores', 'shop_builder', 'lookup_order', 'lookup_warranty'] as $r) {
            $urls[] = $this->generateUrl($r, [], $abs);
        }
        foreach (array_keys(ShopPagesController::LANDING) as $slug) {
            $urls[] = $this->generateUrl('shop_landing', ['slug' => $slug], $abs);
        }
        foreach (array_keys($products->brandCounts()) as $brand) {
            $urls[] = $this->generateUrl('shop_brand', ['slug' => \App\Entity\SanPham::slugify($brand)], $abs);
        }

        foreach ($categories->findAllOrdered() as $c) {
            $urls[] = $this->generateUrl('app_productofcate', ['id' => $c->getId()], $abs);
        }
        foreach ($products->findAllNewestFirst() as $p) {
            $urls[] = $this->generateUrl('app_product', ['slug' => $p->getSlug(), 'id' => $p->getId()], $abs);
        }
        foreach ($news->findAllOrdered() as $n) {
            $urls[] = $this->generateUrl('app_news_detail', ['slug' => $n->getSlug()], $abs);
        }

        $response = $this->render('sitemap.xml.twig', ['urls' => $urls]);
        $response->headers->set('Content-Type', 'application/xml');

        return $response;
    }
}
