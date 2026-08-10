<?php

namespace App\Controller;

use App\Entity\Banner;
use App\Form\BannerType;
use App\Repository\BannerRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/banners')]
class AdminBannerController extends AbstractController
{
    #[Route('', name: 'admin_banner_list', methods: ['GET'])]
    public function list(BannerRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/banner/list.html.twig', ['items' => $repo->findAllOrdered()]);
    }

    #[Route('/new', name: 'admin_banner_new')]
    public function new(Request $req, EntityManagerInterface $em, FileUploader $uploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->handleForm(new Banner(), $req, $em, $uploader, true);
    }

    #[Route('/{id}/edit', name: 'admin_banner_edit', requirements: ['id' => '\d+'])]
    public function edit(Banner $banner, Request $req, EntityManagerInterface $em, FileUploader $uploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->handleForm($banner, $req, $em, $uploader, false);
    }

    #[Route('/{id}/delete', name: 'admin_banner_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Banner $banner, Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete_banner_' . $banner->getId(), $req->request->get('_token'))) {
            $em->remove($banner);
            $em->flush();
            $this->addFlash('success', 'Đã xóa banner.');
        }

        return $this->redirectToRoute('admin_banner_list');
    }

    private function handleForm(Banner $banner, Request $req, EntityManagerInterface $em, FileUploader $uploader, bool $isNew): Response
    {
        $form = $this->createForm(BannerType::class, $banner);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $banner->setImage('uploads/sanpham/' . $uploader->upload($file));
            }
            if (!$banner->getImage()) {
                $this->addFlash('error', 'Vui lòng chọn ảnh banner.');
                return $this->render('admin/banner/form.html.twig', ['form' => $form->createView(), 'banner' => $banner, 'is_new' => $isNew]);
            }
            $em->persist($banner);
            $em->flush();
            $this->addFlash('success', $isNew ? 'Đã thêm banner.' : 'Đã cập nhật banner.');

            return $this->redirectToRoute('admin_banner_list');
        }

        return $this->render('admin/banner/form.html.twig', [
            'form' => $form->createView(),
            'banner' => $banner,
            'is_new' => $isNew,
        ]);
    }
}
