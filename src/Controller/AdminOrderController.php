<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/orders')]
class AdminOrderController extends AbstractController
{
    #[Route('', name: 'admin_order_list', methods: ['GET'])]
    public function list(OrderRepository $orderRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $filters = [
            'status' => $request->query->get('status') ?: null,
            'q' => trim((string) $request->query->get('q')) ?: null,
            'from' => $request->query->get('from') ?: null,
            'to' => $request->query->get('to') ?: null,
        ];

        $orders = $paginator->paginate(
            $orderRepository->filteredQuery($filters),
            $request->query->getInt('page', 1),
            20
        );

        return $this->render('admin/order/list.html.twig', [
            'orders' => $orders,
            'statuses' => Order::STATUSES,
            'filters' => $filters,
            'status_counts' => $orderRepository->countByStatusMap(),
        ]);
    }

    #[Route('/export.csv', name: 'admin_orders_export', methods: ['GET'])]
    public function export(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $rows = [];
        $rows[] = ['Mã đơn', 'Khách hàng', 'Điện thoại', 'Email', 'Địa chỉ', 'Thanh toán', 'Đã trả', 'Trạng thái', 'Tổng tiền', 'Ngày đặt'];
        foreach ($orderRepository->findAllOrdered() as $o) {
            $rows[] = [
                $o->getId(), $o->getCustomerName(), $o->getCustomerPhone(), $o->getCustomerEmail(),
                $o->getFullAddress(), $o->getPaymentMethodLabel(), $o->isPaid() ? 'Có' : 'Không',
                $o->getStatusLabel(), (int) $o->getTotalPrice(),
                $o->getCreatedAt() ? $o->getCreatedAt()->format('d/m/Y H:i') : '',
            ];
        }

        return $this->csv($rows, 'don-hang.csv');
    }

    #[Route('/{id}', name: 'admin_order_detail', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/order/detail.html.twig', [
            'order' => $order,
            'statuses' => Order::STATUSES,
        ]);
    }

    #[Route('/{id}/invoice', name: 'admin_order_invoice', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function invoice(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/order/invoice.html.twig', ['order' => $order]);
    }

    #[Route('/{id}/invoice.pdf', name: 'admin_order_invoice_pdf', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function invoicePdf(Order $order): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $html = $this->renderView('admin/order/invoice_pdf.html.twig', ['order' => $order]);

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4');
        $dompdf->render();

        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="hoa-don-' . $order->getId() . '.pdf"',
        ]);
    }

    /** @param array<int, array<int, string|int>> $rows */
    private function csv(array $rows, string $filename): Response
    {
        $out = "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
        foreach ($rows as $row) {
            $out .= implode(',', array_map(fn ($c) => '"' . str_replace('"', '""', (string) $c) . '"', $row)) . "\r\n";
        }

        return new Response($out, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    #[Route('/{id}/status', name: 'admin_order_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('order_status_' . $order->getId(), $submittedToken)) {
            $this->addFlash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
            return $this->redirectToRoute('admin_order_detail', ['id' => $order->getId()]);
        }

        $status = $request->request->get('status');
        if (!array_key_exists($status, Order::STATUSES)) {
            $this->addFlash('error', 'Trạng thái không hợp lệ.');
            return $this->redirectToRoute('admin_order_detail', ['id' => $order->getId()]);
        }

        $order->setStatus($status);
        $order->setUpdatedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Đơn #' . $order->getId() . ' đã chuyển sang "' . $order->getStatusLabel() . '".');

        return $this->redirectToRoute('admin_order_detail', ['id' => $order->getId()]);
    }
}
