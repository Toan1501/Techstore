<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use App\Service\VnpayService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PaymentController extends AbstractController
{
    /** Start (or retry) a VNPay payment for an order. */
    #[Route('/payment/vnpay/pay/{id}', name: 'app_payment_vnpay_start', requirements: ['id' => '\d+'])]
    public function vnpayStart(Order $order, Request $request, VnpayService $vnpay): Response
    {
        // Only the buyer (session) or the owner may pay.
        $user = $this->getUser();
        $ownsIt = $user && $order->getUser() && $order->getUser() === $user;
        $justPlaced = $request->getSession()->get('last_order_id') === $order->getId();
        if (!$ownsIt && !$justPlaced) {
            throw $this->createAccessDeniedException();
        }
        if (!$vnpay->isConfigured()) {
            $this->addFlash('error', 'Cổng VNPay chưa được cấu hình (thiếu TMN_CODE/HASH_SECRET).');
            return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
        }
        if ($order->isPaid()) {
            return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
        }

        return $this->redirect($vnpay->createPaymentUrl($order, $request));
    }

    /** Browser return URL — for user feedback. IPN is the authoritative update. */
    #[Route('/payment/vnpay/return', name: 'app_payment_vnpay_return')]
    public function vnpayReturn(Request $request, VnpayService $vnpay, OrderRepository $orderRepo, EntityManagerInterface $em): Response
    {
        $params = $request->query->all();

        if (!$vnpay->isValidSignature($params)) {
            $this->addFlash('error', 'Chữ ký thanh toán không hợp lệ.');
            return $this->redirectToRoute('homepage');
        }

        $order = $orderRepo->find((int) ($params['vnp_TxnRef'] ?? 0));
        if (!$order) {
            $this->addFlash('error', 'Không tìm thấy đơn hàng.');
            return $this->redirectToRoute('homepage');
        }

        if ($vnpay->isSuccess($params)) {
            if (!$order->isPaid()) {
                $order->setPaid(true);
                $order->setPaidAt(new \DateTime());
                $order->setStatus(Order::STATUS_CONFIRMED);
                $em->flush();
            }
            $this->addFlash('success', 'Thanh toán VNPay thành công cho đơn #' . $order->getId() . '.');
        } else {
            $this->addFlash('error', 'Thanh toán VNPay không thành công hoặc đã bị hủy.');
        }

        return $this->redirectToRoute('app_order_confirmation', ['id' => $order->getId()]);
    }

    /** Server-to-server IPN — VNPay confirms the transaction here. */
    #[Route('/payment/vnpay/ipn', name: 'app_payment_vnpay_ipn')]
    public function vnpayIpn(Request $request, VnpayService $vnpay, OrderRepository $orderRepo, EntityManagerInterface $em): JsonResponse
    {
        $params = $request->query->all();

        if (!$vnpay->isValidSignature($params)) {
            return new JsonResponse(['RspCode' => '97', 'Message' => 'Invalid signature']);
        }

        $order = $orderRepo->find((int) ($params['vnp_TxnRef'] ?? 0));
        if (!$order) {
            return new JsonResponse(['RspCode' => '01', 'Message' => 'Order not found']);
        }

        if ((int) ($params['vnp_Amount'] ?? 0) !== (int) round((float) $order->getTotalPrice() * 100)) {
            return new JsonResponse(['RspCode' => '04', 'Message' => 'Invalid amount']);
        }

        if ($order->isPaid()) {
            return new JsonResponse(['RspCode' => '02', 'Message' => 'Order already confirmed']);
        }

        if ($vnpay->isSuccess($params)) {
            $order->setPaid(true);
            $order->setPaidAt(new \DateTime());
            $order->setStatus(Order::STATUS_CONFIRMED);
            $em->flush();
        }

        return new JsonResponse(['RspCode' => '00', 'Message' => 'Confirm Success']);
    }
}
