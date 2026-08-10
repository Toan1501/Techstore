<?php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\SanPham;
use App\Repository\OrderRepository;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    #[Route('/product/{id}/review', name: 'app_review_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function add(SanPham $product, Request $request, EntityManagerInterface $em, OrderRepository $orderRepo, ReviewRepository $reviewRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if (!$this->isCsrfTokenValid('review_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token không hợp lệ.');
            return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
        }

        // Only buyers may review, and only once per product.
        if (!$orderRepo->userBoughtProduct($this->getUser(), $product)) {
            $this->addFlash('error', 'Bạn cần mua sản phẩm này trước khi đánh giá.');
            return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
        }
        if ($reviewRepo->hasReviewed($product, $this->getUser())) {
            $this->addFlash('error', 'Bạn đã đánh giá sản phẩm này rồi.');
            return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
        }

        $rating = (int) $request->request->get('rating');
        if ($rating < 1 || $rating > 5) {
            $this->addFlash('error', 'Vui lòng chọn số sao từ 1 đến 5.');
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

        $this->addFlash('success', 'Cảm ơn bạn đã đánh giá!');

        return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
    }
}
