<?php

namespace App\Controller;

use App\Repository\SanPhamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class WishlistController extends AbstractController
{
    #[Route('/yeu-thich', name: 'app_wishlist')]
    public function index(Request $request, SanPhamRepository $repo): Response
    {
        $ids = $request->getSession()->get('wishlist', []);
        $products = $ids ? $repo->findBy(['id' => $ids]) : [];

        return $this->render('homepage/wishlist.html.twig', ['products' => $products]);
    }

    #[Route('/yeu-thich/toggle/{id}', name: 'app_wishlist_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(int $id, Request $request): Response
    {
        $session = $request->getSession();
        $ids = $session->get('wishlist', []);

        if (in_array($id, $ids)) {
            $ids = array_values(array_diff($ids, [$id]));
            $active = false;
            $message = 'Đã bỏ khỏi yêu thích.';
        } else {
            $ids[] = $id;
            $active = true;
            $message = 'Đã thêm vào yêu thích.';
        }
        $session->set('wishlist', $ids);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['ok' => true, 'active' => $active, 'count' => count($ids), 'message' => $message]);
        }

        return new RedirectResponse($request->headers->get('referer') ?: $this->generateUrl('app_wishlist'));
    }
}
