<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\MomoService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    /** Start (or retry) a MoMo payment for an order. */
    #[Route('/payment/momo/pay/{id}', name: 'app_payment_momo_start', requirements: ['id' => '\d+'])]
    public function momoStart(Order $order, Request $request, MomoService $momo): Response
    {
        // Only the buyer (session) or the owner may pay.
        $user = $this->getUser();
        $ownsIt = $user && $order->getUser() && $order->getUser() === $user;
        $justPlaced = $request->getSession()->get('last_order_id') === $order->getId();
        if (!$ownsIt && !$justPlaced) {
            throw $this->createAccessDeniedException();
        }
        if (!$momo->isConfigured()) {
            $this->addFlash('error', 'Cổng MoMo chưa được cấu hình (thiếu PARTNER_CODE/ACCESS_KEY/SECRET_KEY).');
            return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
        }
        if ($order->isPaid()) {
            return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
        }

        $payUrl = $momo->createPaymentUrl($order, $request);
        if ($payUrl === null) {
            $this->addFlash('error', 'Không thể khởi tạo thanh toán MoMo. Vui lòng thử lại.');
            return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
        }

        return $this->redirect($payUrl);
    }

    /** Browser return URL — for user feedback. IPN is the authoritative update. */
    #[Route('/payment/momo/return', name: 'app_payment_momo_return')]
    public function momoReturn(Request $request, MomoService $momo, OrderRepository $orderRepo, EntityManagerInterface $em): Response
    {
        $params = $request->query->all();

        if (!$momo->isValidSignature($params)) {
            $this->addFlash('error', 'Chữ ký thanh toán không hợp lệ.');
            return $this->redirectToRoute('homepage');
        }

        $order = $orderRepo->find($momo->orderIdFromCallback($params));
        if (!$order) {
            $this->addFlash('error', 'Không tìm thấy đơn hàng.');
            return $this->redirectToRoute('homepage');
        }

        if ($momo->isSuccess($params)) {
            if (!$order->isPaid()) {
                $order->setPaid(true);
                $order->setPaidAt(new \DateTime());
                $order->setStatus(Order::STATUS_CONFIRMED);
                $em->flush();
            }
            $this->addFlash('success', 'Thanh toán MoMo thành công cho đơn #' . $order->getId() . '.');
        } else {
            $this->addFlash('error', 'Thanh toán MoMo không thành công hoặc đã bị hủy.');
        }

        return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
    }

    /** Server-to-server IPN — MoMo confirms the transaction here (JSON POST). */
    #[Route('/payment/momo/ipn', name: 'app_payment_momo_ipn', methods: ['POST'])]
    public function momoIpn(Request $request, MomoService $momo, OrderRepository $orderRepo, EntityManagerInterface $em): JsonResponse
    {
        $params = json_decode($request->getContent(), true) ?: [];

        if (!$momo->isValidSignature($params)) {
            return new JsonResponse(['resultCode' => 97, 'message' => 'Invalid signature']);
        }

        $order = $orderRepo->find($momo->orderIdFromCallback($params));
        if (!$order) {
            return new JsonResponse(['resultCode' => 1, 'message' => 'Order not found']);
        }

        if ((int) ($params['amount'] ?? 0) !== (int) round((float) $order->getTotalPrice())) {
            return new JsonResponse(['resultCode' => 4, 'message' => 'Invalid amount']);
        }

        if (!$order->isPaid() && $momo->isSuccess($params)) {
            $order->setPaid(true);
            $order->setPaidAt(new \DateTime());
            $order->setStatus(Order::STATUS_CONFIRMED);
            $em->flush();
        }

        // MoMo expects HTTP 204 (No Content) to acknowledge the IPN.
        return new JsonResponse(['resultCode' => 0, 'message' => 'Confirm Success'], Response::HTTP_NO_CONTENT);
    }
}
