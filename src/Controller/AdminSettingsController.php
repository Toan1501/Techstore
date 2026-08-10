<?php

namespace App\Controller;

use App\Form\StoreSettingsType;
use App\Repository\StoreSettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminSettingsController extends AbstractController
{
    #[Route('/admin/settings', name: 'admin_settings')]
    public function edit(Request $req, StoreSettingsRepository $repo, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $settings = $repo->get();
        $form = $this->createForm(StoreSettingsType::class, $settings);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Đã lưu cài đặt cửa hàng.');

            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/settings.html.twig', ['form' => $form->createView()]);
    }
}
