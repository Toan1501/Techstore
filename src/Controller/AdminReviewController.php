<?php

namespace App\Controller;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/reviews')]
class AdminReviewController extends AbstractController
{
    #[Route('', name: 'admin_review_list', methods: ['GET'])]
    public function list(ReviewRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/review/list.html.twig', [
            'items' => $repo->findBy([], ['createdAt' => 'DESC', 'id' => 'DESC']),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_review_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Review $review, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete_review_' . $review->getId(), $request->request->get('_token'))) {
            $em->remove($review);
            $em->flush();
            $this->addFlash('success', 'Đã xóa đánh giá.');
        }

        return $this->redirectToRoute('admin_review_list');
    }
}
