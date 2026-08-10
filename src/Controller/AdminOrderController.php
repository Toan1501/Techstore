<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/orders')]
class AdminOrderController extends AbstractController
{
    #[Route('', name: 'admin_order_list', methods: ['GET'])]
    public function list(OrderRepository $orderRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/order/list.html.twig', [
            'orders' => $orderRepository->findAllOrdered(),
            'statuses' => Order::STATUSES,
        ]);
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

    #[Route('/{id}/status', name: 'admin_order_status', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $submittedToken = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('order_status_' . $order->getId(), $submittedToken)) {
            $this->addFlash('error', 'Invalid CSRF token.');
            return $this->redirectToRoute('admin_order_detail', ['id' => $order->getId()]);
        }

        $status = $request->request->get('status');
        if (!array_key_exists($status, Order::STATUSES)) {
            $this->addFlash('error', 'Invalid status.');
            return $this->redirectToRoute('admin_order_detail', ['id' => $order->getId()]);
        }

        $order->setStatus($status);
        $order->setUpdatedAt(new \DateTime());
        $em->flush();

        $this->addFlash('success', 'Order #' . $order->getId() . ' status updated to "' . $status . '".');

        return $this->redirectToRoute('admin_order_detail', ['id' => $order->getId()]);
    }
}
