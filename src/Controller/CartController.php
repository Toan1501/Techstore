<?php

namespace App\Controller;

use App\Cart\CartManager;
use App\Entity\SanPham;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CartController extends AbstractController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    #[Route('/cart', name: 'app_cart')]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $cart_manager = $session->get('cart', new CartManager());
        return $this->render('cart/index.html.twig', [
            "cart_manager"=>$cart_manager
        ]);
    }

    /** Fresh mini-cart drawer contents (the drawer is otherwise rendered once with the page). */
    #[Route('/cart/mini', name: 'app_cart_mini', methods: ['GET'])]
    public function mini(): Response
    {
        $response = $this->render('partials/_mini_cart.html.twig');
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    #[Route('/cart/add/{product_id}', name: 'app_cart_add', methods: 'post')]
    public function add(int $product_id, Request $request, EntityManagerInterface $em): Response
    {
        $isAjax = $request->isXmlHttpRequest();
        $product = $em->find(SanPham::class, $product_id);

        if (!$product || !$product->isInStock()) {
            if ($isAjax) {
                return new JsonResponse(['ok' => false, 'message' => 'Sản phẩm này hiện không còn bán.'], 400);
            }
            $this->addFlash('error', 'Sản phẩm này hiện không còn bán.');
            return new RedirectResponse($this->urlGenerator->generate('app_cart'));
        }

        $qty = max(1, (int) $request->request->get('qty', 1));
        $session = $request->getSession();
        $cart_manager = $session->get('cart', new CartManager());
        $cart_manager->addItem($product, $qty);
        $session->set('cart', $cart_manager);

        if ($isAjax) {
            return new JsonResponse([
                'ok' => true,
                'count' => $cart_manager->getTotalItems(),
                'message' => 'Đã thêm "' . $product->getName() . '" vào giỏ.',
            ]);
        }

        // "Buy now" jumps straight to checkout.
        if ($request->request->get('buynow')) {
            return new RedirectResponse($this->urlGenerator->generate('app_order'));
        }

        return new RedirectResponse($this->urlGenerator->generate('app_cart'));
    }
    #[Route('/cart/remove/{product_id}', name: 'app_cart_remove', methods: ['POST'])]
    public function remove(int $product_id, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('cart_remove_' . $product_id, (string) $request->request->get('_token'))) {
            return new RedirectResponse($this->urlGenerator->generate('app_cart'));
        }
        $session = $request->getSession();
        $cart_manager = $session->get('cart', new CartManager());
        $cart_manager->removeItemById($product_id);
        $session->set('cart', $cart_manager);
        return new RedirectResponse($this->urlGenerator->generate('app_cart'));
    }

    #[Route('/cart/coupon', name: 'app_cart_coupon', methods: ['POST'])]
    public function applyCoupon(Request $request, \App\Repository\CouponRepository $couponRepo): Response
    {
        $session = $request->getSession();
        $cart = $session->get('cart', new CartManager());
        $subtotal = $cart->getAmount();
        $code = strtoupper(trim((string) $request->request->get('coupon_code', '')));

        if ($code === '') {
            $session->remove('coupon');
        } else {
            $coupon = $couponRepo->findByCode($code);
            if (!$coupon || !$coupon->isValidNow()) {
                $session->remove('coupon');
                $this->addFlash('error', 'Mã giảm giá không hợp lệ hoặc đã hết hạn.');
            } elseif ($subtotal < $coupon->getMinOrder()) {
                $session->remove('coupon');
                $this->addFlash('error', 'Đơn hàng tối thiểu ' . number_format($coupon->getMinOrder(), 0, ',', '.') . ' ₫ để dùng mã này.');
            } else {
                $session->set('coupon', $coupon->getCode());
                $this->addFlash('success', 'Đã áp dụng mã giảm giá ' . $coupon->getCode() . '.');
            }
        }

        return new RedirectResponse($this->urlGenerator->generate('app_order'));
    }

    #[Route('/cart/coupon/remove', name: 'app_cart_coupon_remove')]
    public function removeCoupon(Request $request): Response
    {
        $request->getSession()->remove('coupon');

        return new RedirectResponse($this->urlGenerator->generate('app_order'));
    }

    #[Route('/cart/update', name: 'app_cart_update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('cart_update', (string) $request->request->get('_token'))) {
            return new RedirectResponse($this->urlGenerator->generate('app_cart'));
        }
        $product = $em->find(SanPham::class, (int) $request->request->get('product_id'));
        if ($product) {
            $qty = max(1, (int) $request->request->get('qty', 1));
            $stock = (int) $product->getStock();
            if ($stock > 0 && $qty > $stock) {
                $qty = $stock;
                $this->addFlash('warning', 'Chỉ còn ' . $stock . ' sản phẩm "' . $product->getName() . '" trong kho.');
            }
            $session = $request->getSession();
            $cart_manager = $session->get('cart', new CartManager());
            $cart_manager->updateItem($product, $qty);
            $session->set('cart', $cart_manager);
        }
        return new RedirectResponse($this->urlGenerator->generate('app_cart'));
    }
}
