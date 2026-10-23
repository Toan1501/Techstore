<?php

namespace App\Controller;

use App\Cart\CartManager;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\SanPham;
use App\Form\OrderFormType;
use App\Repository\OrderRepository;
use App\Service\OrderMailer;
use App\Service\ShippingCalculator;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderController extends AbstractController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    #[Route('/my-orders', name: 'app_my_orders')]
    public function myOrders(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        return $this->render('homepage/orders.html.twig', [
            'orders' => $orderRepository->findByUser($this->getUser()),
        ]);
    }

    #[Route('/my-orders/{id}', name: 'app_order_detail_customer', requirements: ['id' => '\d+'])]
    public function customerOrderDetail(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        return $this->render('homepage/order_detail.html.twig', ['order' => $order]);
    }

    #[Route('/my-orders/{id}/cancel', name: 'app_order_cancel', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function cancelOrder(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        if ($order->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
        if (!$this->isCsrfTokenValid('cancel_order_' . $order->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token không hợp lệ.');
            return $this->redirectToRoute('app_order_detail_customer', ['id' => $order->getId()]);
        }
        if ($order->getStatus() !== Order::STATUS_PENDING) {
            $this->addFlash('error', 'Chỉ có thể hủy đơn đang chờ xử lý.');
            return $this->redirectToRoute('app_order_detail_customer', ['id' => $order->getId()]);
        }

        // restore stock
        foreach ($order->getItems() as $item) {
            if ($item->getProduct()) {
                $item->getProduct()->setStock((int) $item->getProduct()->getStock() + (int) $item->getQuantity());
            }
        }
        $order->setStatus(Order::STATUS_CANCELLED);
        $order->setUpdatedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Đã hủy đơn hàng #' . $order->getId() . '.');

        return $this->redirectToRoute('app_order_detail_customer', ['id' => $order->getId()]);
    }

    #[Route('/order', name: 'app_order')]
    public function index(EntityManagerInterface $em, Request $req, ShippingCalculator $shipping, OrderMailer $mailer, \App\Repository\CouponRepository $couponRepo, \App\Service\MomoService $momo): Response
    {
        $order = new Order();
        $form = $this->createForm(OrderFormType::class, $order);
        $form->handleRequest($req);
        $session = $req->getSession();
        $cart_manager = $session->get('cart', new CartManager());

        // Resolve applied coupon (session) against the current cart subtotal.
        $subtotalNow = $cart_manager->getAmount();
        $appliedCoupon = null;
        $discountNow = 0.0;
        if ($code = $session->get('coupon')) {
            $c = $couponRepo->findByCode($code);
            if ($c && $c->isValidNow() && $subtotalNow >= $c->getMinOrder()) {
                $appliedCoupon = $c;
                $discountNow = $c->computeDiscount($subtotalNow);
            } else {
                $session->remove('coupon');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            if (empty($cart_manager->getItems())) {
                $this->addFlash('error', 'Your cart is empty.');
                return new RedirectResponse($this->urlGenerator->generate('app_cart'));
            }

            $order = $form->getData();
            $em->getConnection()->beginTransaction();
            try {
                $order->setStatus('pending');
                $order->setCreatedAt(new \DateTime());
                $order->setUser($this->getUser());
                $em->persist($order);

                $itemsSubtotal = 0.0;
                foreach ($cart_manager->getItems() as $cart_item) {
                    // Lock the product row for the duration of the transaction so the
                    // read-check-decrement is atomic (prevents overselling under concurrency).
                    $product = $em->find(SanPham::class, $cart_item->getProduct()->getId(), LockMode::PESSIMISTIC_WRITE);
                    if (!$product) {
                        throw new Exception('Product no longer available.');
                    }
                    $qty = max(1, (int) $cart_item->quantity);
                    if ((int) $product->getStock() < $qty) {
                        throw new Exception('Not enough stock for "' . $product->getName() . '".');
                    }
                    $orderItem = new OrderItem();
                    $orderItem->setProduct($product);
                    $orderItem->setQuantity($qty);
                    $orderItem->setPrice($product->getPrice());
                    $orderItem->setOrderRef($order);
                    $em->persist($orderItem);
                    $product->setStock((int) $product->getStock() - $qty);
                    $itemsSubtotal += (float) $product->getPrice() * $qty;
                }

                // Re-validate the coupon under a row lock and consume one use atomically,
                // so a usage-limited code can't be over-redeemed under concurrency.
                $discount = 0.0;
                $couponCode = null;
                if ($appliedCoupon) {
                    $coupon = $em->find(\App\Entity\Coupon::class, $appliedCoupon->getId(), LockMode::PESSIMISTIC_WRITE);
                    if ($coupon && $coupon->isValidNow() && $itemsSubtotal >= (float) $coupon->getMinOrder()) {
                        $discount = $coupon->computeDiscount($itemsSubtotal);
                        if ($discount > 0) {
                            $couponCode = $coupon->getCode();
                            $coupon->setUsageCount($coupon->getUsageCount() + 1);
                        }
                    }
                }

                // Totals are derived from the DB-authoritative subtotal — never from
                // session/client prices — so admin price changes can't be exploited.
                $fee = $shipping->calculate($order->getShippingProvince(), $itemsSubtotal);
                $order->setShippingFee($fee);
                $order->setDiscount($discount);
                $order->setCouponCode($couponCode);
                $order->setTotalPrice($itemsSubtotal - $discount + $fee);

                $em->flush();
                $em->getConnection()->commit();
                $session->set('cart', new CartManager());
                $session->set('last_order_id', $order->getId());
                $session->remove('coupon');
            } catch (Exception $e) {
                $em->getConnection()->rollBack();
                $this->addFlash('error', 'Unable to create order: ' . $e->getMessage());
                return new RedirectResponse($this->urlGenerator->generate('app_cart'));
            }

            $mailer->sendConfirmation($order);

            // Online payment: hand off to the MoMo gateway (if configured).
            if ($order->getPaymentMethod() === 'momo' && $momo->isConfigured()) {
                $payUrl = $momo->createPaymentUrl($order, $req);
                if ($payUrl !== null) {
                    return new RedirectResponse($payUrl);
                }
                $this->addFlash('error', 'Không thể khởi tạo thanh toán MoMo. Đơn hàng đã được ghi nhận, vui lòng thử thanh toán lại.');
            }

            return new RedirectResponse($this->urlGenerator->generate('app_order_confirmation', ['id' => $order->getId()]));
        }

        return $this->render('order/index.html.twig', [
            'order_form' => $form->createView(),
            'cart_manager' => $cart_manager,
            'shipping_fee_map' => $shipping->feeMap(),
            'free_ship_threshold' => $shipping->freeThreshold(),
            'applied_coupon' => $appliedCoupon,
            'discount' => $discountNow,
        ]);
    }

    #[Route('/order/{id}/confirmation', name: 'app_order_confirmation', requirements: ['id' => '\d+'])]
    public function confirmation(Order $order, Request $req): Response
    {
        // Access: the owner (if logged in) or the buyer who just placed it (session token).
        $user = $this->getUser();
        $ownsIt = $user && $order->getUser() && $order->getUser() === $user;
        $justPlaced = $req->getSession()->get('last_order_id') === $order->getId();

        if (!$ownsIt && !$justPlaced) {
            throw $this->createAccessDeniedException('You cannot view this order.');
        }

        return $this->render('order/confirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
