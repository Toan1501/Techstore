<?php

namespace App\Controller;

use App\Repository\SanPhamRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CompareController extends AbstractController
{
    private const MAX = 4;

    #[Route('/so-sanh', name: 'app_compare')]
    public function index(Request $request, SanPhamRepository $repo): Response
    {
        $ids = $request->getSession()->get('compare', []);
        $products = $ids ? $repo->findBy(['id' => $ids]) : [];

        // union of spec keys
        $specKeys = [];
        foreach ($products as $p) {
            foreach ($p->getSpecList() as $pair) {
                $specKeys[$pair[0]] = true;
            }
        }

        return $this->render('homepage/compare.html.twig', [
            'products' => $products,
            'spec_keys' => array_keys($specKeys),
        ]);
    }

    #[Route('/so-sanh/toggle/{id}', name: 'app_compare_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggle(int $id, Request $request): Response
    {
        $session = $request->getSession();
        $ids = $session->get('compare', []);

        if (in_array($id, $ids)) {
            $ids = array_values(array_diff($ids, [$id]));
            $active = false;
            $message = 'Đã bỏ khỏi so sánh.';
        } elseif (count($ids) >= self::MAX) {
            if ($request->isXmlHttpRequest()) {
                return new JsonResponse(['ok' => false, 'count' => count($ids), 'message' => 'Chỉ so sánh tối đa ' . self::MAX . ' sản phẩm.']);
            }
            $this->addFlash('error', 'Chỉ so sánh tối đa ' . self::MAX . ' sản phẩm.');
            return new RedirectResponse($request->headers->get('referer') ?: $this->generateUrl('app_compare'));
        } else {
            $ids[] = $id;
            $active = true;
            $message = 'Đã thêm vào so sánh.';
        }
        $session->set('compare', $ids);

        if ($request->isXmlHttpRequest()) {
            return new JsonResponse(['ok' => true, 'active' => $active, 'count' => count($ids), 'message' => $message]);
        }

        return new RedirectResponse($request->headers->get('referer') ?: $this->generateUrl('app_compare'));
    }

    #[Route('/so-sanh/clear', name: 'app_compare_clear')]
    public function clear(Request $request): Response
    {
        $request->getSession()->remove('compare');

        return new RedirectResponse($request->headers->get('referer') ?: $this->generateUrl('homepage'));
    }
}
