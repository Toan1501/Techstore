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
