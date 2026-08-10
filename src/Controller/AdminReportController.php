<?php

namespace App\Controller;

use App\Repository\OrderItemRepository;
use App\Repository\OrderRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminReportController extends AbstractController
{
    #[Route('/admin/reports', name: 'admin_reports')]
    public function index(OrderRepository $orderRepo, OrderItemRepository $itemRepo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $revByDay = $orderRepo->revenueByDay(30);
        $labels = [];
        $values = [];
        foreach ($revByDay as $day => $rev) {
            $labels[] = (new \DateTime($day))->format('d/m');
            $values[] = $rev;
        }

        $statusMap = $orderRepo->countByStatusMap();
        $totalOrders = array_sum($statusMap);
        $revenue = $orderRepo->totalRevenue();
        $completed = $statusMap['completed'] ?? 0;

        return $this->render('admin/reports.html.twig', [
            'chart_labels' => $labels,
            'chart_values' => $values,
            'revenue' => $revenue,
            'total_orders' => $totalOrders,
            'avg_order' => $completed > 0 ? $revenue / $completed : 0,
            'completed_orders' => $completed,
            'best_sellers' => $itemRepo->bestSellers(10),
            'revenue_by_category' => $itemRepo->revenueByCategory(),
        ]);
    }
}
