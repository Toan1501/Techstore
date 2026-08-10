<?php

namespace App\Controller;

use App\Entity\Coupon;
use App\Form\CouponType;
use App\Repository\CouponRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/coupons')]
class AdminCouponController extends AbstractController
{
    #[Route('', name: 'admin_coupon_list', methods: ['GET'])]
    public function list(CouponRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/coupon/list.html.twig', ['items' => $repo->findBy([], ['id' => 'DESC'])]);
    }

    #[Route('/new', name: 'admin_coupon_new')]
    public function new(Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->handleForm(new Coupon(), $req, $em, true);
    }

    #[Route('/{id}/edit', name: 'admin_coupon_edit', requirements: ['id' => '\d+'])]
    public function edit(Coupon $coupon, Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->handleForm($coupon, $req, $em, false);
    }

    #[Route('/{id}/delete', name: 'admin_coupon_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Coupon $coupon, Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete_coupon_' . $coupon->getId(), $req->request->get('_token'))) {
            $em->remove($coupon);
            $em->flush();
            $this->addFlash('success', 'Đã xóa mã giảm giá.');
        }

        return $this->redirectToRoute('admin_coupon_list');
    }

    private function handleForm(Coupon $coupon, Request $req, EntityManagerInterface $em, bool $isNew): Response
    {
        $form = $this->createForm(CouponType::class, $coupon);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($coupon);
            $em->flush();
            $this->addFlash('success', $isNew ? 'Đã thêm mã giảm giá.' : 'Đã cập nhật mã giảm giá.');

            return $this->redirectToRoute('admin_coupon_list');
        }

        return $this->render('admin/coupon/form.html.twig', ['form' => $form->createView(), 'is_new' => $isNew]);
    }
}
