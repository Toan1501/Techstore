<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\SanPham;
use App\Entity\Category;
use App\Form\CategoryType;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    public function __construct(private UrlGeneratorInterface $urlGenerator)
    {
    }
    #[Route('/adcategory', name: 'ad_category')]
    public function index(EntityManagerInterface $em, Request $req, FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $sp = new Category();
        $form = $this->createForm(CategoryType::class, $sp);
        $form->handleRequest($req);

        if($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $file = $form->get("photo")->getData();
            $fileName = $fileUploader->upload($file);
            $data->setPhoto($fileName);

            $em->persist($data);
            $em->flush();
            return new RedirectResponse($this->urlGenerator->generate('app_category'));
        }

        return $this->render('category/index.html.twig', [
            'category_form' => $form->createView(),
        ]);
    }
    #[Route('/category/ds', name: 'app_ds_category')]
    public function list_cat(\App\Repository\CategoryRepository $categoryRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('category/list.html.twig', [
            'data' => $categoryRepository->findAllOrdered(),
        ]);
    }
    #[Route('/category_ad/{id}', name: 'app_edit_category')]
    public function edit(EntityManagerInterface $em, int $id,Request $req,FileUploader $fileUploader): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        $cg = $em->find(Category::class, $id);
        $form = $this->createForm(CategoryType::class, $cg);
        $form -> handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()){
            $data = $form->getData();

            $file = $form-> get("photo")->getData();
            if ($file){
            $fileName = $fileUploader->upload($file);
            $data -> setPhoto($fileName);
            }
            $cg->setName($data->getName());
            $em->flush();
            return new RedirectResponse($this->urlGenerator->generate('app_ds_category'));
        }
        return $this->render('category/index.html.twig', [
            'category_form' => $form->createView(),
        ]);
    }
    #[Route('/category/{id}/delete', name: 'app_delete_category', methods: ['POST'])]
    public function deletecat(EntityManagerInterface $em, int $id,Request $req): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('delete_category_' . $id, $req->request->get('_token'))) {
            return new RedirectResponse($this->urlGenerator->generate('app_ds_category'));
        }
        $cg = $em->find(Category::class, $id);
        if ($cg) {
            try {
                $em->remove($cg);
                $em->flush();
                $this->addFlash('success', 'Đã xóa danh mục.');
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Không thể xóa: danh mục còn sản phẩm.');
            }
        }
        return new RedirectResponse($this->urlGenerator->generate('app_ds_category'));
    }
}
