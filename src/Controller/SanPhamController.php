<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\SanPham;
use App\Entity\Category;
use App\Entity\ProductImage;
use App\Form\SanPhamType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;

class SanPhamController extends AbstractController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }

    /** Upload any gallery images from the (unmapped) galleryImages field into ProductImage rows. */
    private function handleGallery($form, SanPham $product, FileUploader $fileUploader): void
    {
        $files = $form->get('galleryImages')->getData();
        if (!$files) {
            return;
        }
        $pos = $product->getImages()->count();
        foreach ($files as $file) {
            if (!$file) {
                continue;
            }
            $img = new ProductImage();
            $img->setFilename($fileUploader->upload($file));
            $img->setPosition($pos++);
            $product->addImage($img);
        }
    }

    #[Route('/san/pham', name: 'app_san_pham')]
    public function index(EntityManagerInterface $em, Request $req, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $sp = new SanPham();
        $form = $this->createForm(SanPhamType::class, $sp);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $form->get("photo")->getData();
            if (!$file) {
                $this->addFlash('error', 'Please choose a product photo.');
                return $this->render('san_pham/index.html.twig', [
                    'sp_form' => $form->createView(),
                ]);
            }
            $fileName = $fileUploader->upload($file);
            $data->setPhoto($fileName);
            $this->handleGallery($form, $data, $fileUploader);

            $em->persist($data);
            $em->flush();
            $this->addFlash('success', 'Product created.');
            return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
        }

        return $this->render('san_pham/index.html.twig', [
            'sp_form' => $form->createView(),
        ]);
    }

    #[Route('/san/pham/ds', name: 'app_ds_san_pham')]
    public function list_sp(\App\Repository\SanPhamRepository $sanPhamRepository): Response
        {
            $this->denyAccessUnlessGranted('ROLE_ADMIN');

            return $this->render('san_pham/list.html.twig', [
                "data" => $sanPhamRepository->findAllNewestFirst(),
            ]);
        }

        #[Route('/san/pham/{id}', name: 'app_edit_san_pham')]
        public function edit(EntityManagerInterface $em, int $id, Request $req, FileUploader $fileUploader): Response
            {
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
                $sp = $em->find(SanPham::class, $id);
                $form = $this->createForm(SanPhamType::class, $sp);
                $form->handleRequest($req);
                
                if($form->isSubmitted() && $form->isValid()) {
                    $data = $form->getData();
                    
                    $file = $form->get("photo")->getData();
                    if($file) {
                        $fileName = $fileUploader->upload($file);
                        $data->setPhoto($fileName);
                    }
                    $this->handleGallery($form, $sp, $fileUploader);
                    $em->flush();
                    $this->addFlash('success', 'Product updated.');
                    return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
                }
                return $this->render('san_pham/index.html.twig', [
                    'sp_form' => $form->createView(),
                ]);
            }

        #[Route('/san/pham/{id}/delete', name: 'app_delete_san_pham', methods: ['POST'])]
        public function delete(EntityManagerInterface $em, int $id, Request $req): Response
            {
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
                if (!$this->isCsrfTokenValid('delete_product_' . $id, $req->request->get('_token'))) {
                    return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
                }
                $sp = $em->find(SanPham::class, $id);
                if ($sp) {
                    try {
                        $em->remove($sp);
                        $em->flush();
                        $this->addFlash('success', 'Đã xóa sản phẩm.');
                    } catch (\Throwable $e) {
                        $this->addFlash('error', 'Không thể xóa: sản phẩm đã có trong đơn hàng.');
                    }
                }

                return new RedirectResponse($this->urlGenerator->generate('app_ds_san_pham'));
            }

        #[Route('/cate/{id}', name: 'app_ds_san_pham_in_category')]
        public function listSPinCate(EntityManagerInterface $em, int $id, Request $req): Response
            {
                $cate = $em->find(Category::class, $id);
                $lSp = $cate->getSanPhams();
                return $this->render('san_pham/list.html.twig', [
                    "data"=>$lSp
                ]);
            }
            
}
