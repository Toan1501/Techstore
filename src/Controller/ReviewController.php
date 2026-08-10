<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\SanPham;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    #[Route('/product/{id}/review', name: 'app_review_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(SanPham $product, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('review_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
        }

        $rating = (int) $request->request->get('rating');
        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'Please select a rating between 1 and 5 stars.');
            return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
        }

        $review = new Review();
        $review->setProduct($product)
            ->setUser($this->getUser())
            ->setRating($rating)
            ->setComment(trim((string) $request->request->get('comment')) ?: null)
            ->setCreatedAt(new \DateTime());

        $em->persist($review);
        $em->flush();

        $this->addFlash('success', 'Thank you for your review!');

        return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
    }
}
