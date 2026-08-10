<?php

namespace App\Controller;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NewsController extends AbstractController
{
    #[Route('/tin-tuc', name: 'app_news')]
    public function list(NewsRepository $newsRepository): Response
    {
        return $this->render('news/list.html.twig', [
            'articles' => $newsRepository->findAllOrdered(),
        ]);
    }

    #[Route('/tin-tuc/{slug}', name: 'app_news_detail')]
    public function detail(string $slug, NewsRepository $newsRepository): Response
    {
        $article = $newsRepository->findOneBy(['slug' => $slug]);
        if (!$article) {
            throw $this->createNotFoundException('Bài viết không tồn tại.');
        }

        $others = array_filter($newsRepository->findAllOrdered(), fn ($a) => $a->getId() !== $article->getId());

        return $this->render('news/detail.html.twig', [
            'article' => $article,
            'others' => array_slice($others, 0, 5),
        ]);
    }
}
