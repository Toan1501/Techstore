<?php

namespace App\Controller;

use App\Entity\Order;
use App\Repository\CategoryRepository;
use App\Repository\OrderRepository;
use App\Repository\SanPhamRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function dashboard(
        OrderRepository $orderRepo,
        SanPhamRepository $productRepo,
        CategoryRepository $categoryRepo,
        UserRepository $userRepo,
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $statusMap = $orderRepo->countByStatusMap();
        $totalOrders = array_sum($statusMap);

        $revByDay = $orderRepo->revenueByDay(14);
        $chartLabels = [];
        $chartValues = [];
        foreach ($revByDay as $day => $rev) {
            $chartLabels[] = (new \DateTime($day))->format('d/m');
            $chartValues[] = $rev;
        }

        return $this->render('admin/dashboard.html.twig', [
            'chart_labels' => $chartLabels,
            'chart_values' => $chartValues,
            'revenue' => $orderRepo->totalRevenue(),
            'total_orders' => $totalOrders,
            'pending_orders' => $statusMap[Order::STATUS_PENDING] ?? 0,
            'total_products' => $productRepo->count([]),
            'total_categories' => $categoryRepo->count([]),
            'total_users' => $userRepo->count([]),
            'status_map' => $statusMap,
            'statuses' => Order::STATUSES,
            'recent_orders' => $orderRepo->findRecent(6),
            'low_stock' => $productRepo->findLowStock(5, 8),
        ]);
    }
}
