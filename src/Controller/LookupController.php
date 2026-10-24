<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** Guest order / warranty lookup: order id + the phone or email used at checkout. */
class LookupController extends AbstractController
{
    #[Route('/tra-cuu-don-hang', name: 'lookup_order', methods: ['GET', 'POST'])]
    public function order(Request $request, OrderRepository $orders): Response
    {
        [$order, $error] = $this->resolve($request, $orders);

        return $this->render('lookup/order.html.twig', ['order' => $order, 'error' => $error]);
    }

    #[Route('/tra-cuu-bao-hanh', name: 'lookup_warranty', methods: ['GET', 'POST'])]
    public function warranty(Request $request, OrderRepository $orders): Response
    {
        [$order, $error] = $this->resolve($request, $orders);
        $items = [];
        if ($order) {
            $start = $order->getCreatedAt();
            foreach ($order->getItems() as $it) {
                $p = $it->getProduct();
                $months = $p ? (int) preg_replace('/\D/', '', (string) $p->getWarranty()) : 0;
                $end = ($start && $months > 0) ? (clone $start)->modify("+{$months} months") : null;
                $items[] = [
                    'name' => $p ? $p->getName() : '(sản phẩm đã xóa)',
                    'qty' => $it->getQuantity(),
                    'months' => $months,
                    'end' => $end,
                    'daysLeft' => $end ? (int) floor(($end->getTimestamp() - time()) / 86400) : null,
                ];
            }
        }

        return $this->render('lookup/warranty.html.twig', ['order' => $order, 'error' => $error, 'items' => $items]);
    }

    /** @return array{0: ?Order, 1: ?string} */
    private function resolve(Request $request, OrderRepository $orders): array
    {
        if (!$request->isMethod('POST')) {
            return [null, null];
        }
        if (!$this->isCsrfTokenValid('lookup', (string) $request->request->get('_token'))) {
            return [null, 'Phiên làm việc không hợp lệ, vui lòng thử lại.'];
        }
        $id = (int) preg_replace('/\D/', '', (string) $request->request->get('order_id'));
        $contact = trim((string) $request->request->get('contact'));
        if ($id <= 0 || $contact === '') {
            return [null, 'Vui lòng nhập mã đơn và số điện thoại hoặc email.'];
        }
        $order = $orders->find($id);
        if ($order) {
            $digits = preg_replace('/\D/', '', $contact);
            $phoneOk = $digits !== '' && preg_replace('/\D/', '', (string) $order->getCustomerPhone()) === $digits;
            $emailOk = strcasecmp(trim((string) $order->getCustomerEmail()), $contact) === 0;
            if ($phoneOk || $emailOk) {
                return [$order, null];
            }
        }

        return [null, 'Không tìm thấy đơn hàng khớp với mã và số điện thoại/email đã nhập.'];
    }
}
