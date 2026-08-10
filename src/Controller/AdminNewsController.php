<?php

namespace App\Controller;

use App\Entity\News;
use App\Form\NewsType;
use App\Repository\NewsRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/news')]
class AdminNewsController extends AbstractController
{
    #[Route('', name: 'admin_news_list', methods: ['GET'])]
    public function list(NewsRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/news/list.html.twig', ['items' => $repo->findAllOrdered()]);
    }

    #[Route('/new', name: 'admin_news_new')]
    public function new(Request $req, EntityManagerInterface $em, FileUploader $uploader, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $news = new News();
        return $this->handleForm($news, $req, $em, $uploader, $slugger, true);
    }

    #[Route('/{id}/edit', name: 'admin_news_edit', requirements: ['id' => '\d+'])]
    public function edit(News $news, Request $req, EntityManagerInterface $em, FileUploader $uploader, SluggerInterface $slugger): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->handleForm($news, $req, $em, $uploader, $slugger, false);
    }

    #[Route('/{id}/delete', name: 'admin_news_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(News $news, Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete_news_' . $news->getId(), $req->request->get('_token'))) {
            $em->remove($news);
            $em->flush();
            $this->addFlash('success', 'Đã xóa bài viết.');
        }

        return $this->redirectToRoute('admin_news_list');
    }

    private function handleForm(News $news, Request $req, EntityManagerInterface $em, FileUploader $uploader, SluggerInterface $slugger, bool $isNew): Response
    {
        $form = $this->createForm(NewsType::class, $news);
        $form->handleRequest($req);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('imageFile')->getData();
            if ($file) {
                $news->setImage('uploads/sanpham/' . $uploader->upload($file));
            }
            if (!$news->getSlug()) {
                $news->setSlug(strtolower($slugger->slug($news->getTitle())->toString()));
            }
            if ($isNew) {
                $news->setCreatedAt(new \DateTime());
            }
            $em->persist($news);
            $em->flush();
            $this->addFlash('success', $isNew ? 'Đã thêm bài viết.' : 'Đã cập nhật bài viết.');

            return $this->redirectToRoute('admin_news_list');
        }

        return $this->render('admin/news/form.html.twig', [
            'form' => $form->createView(),
            'news' => $news,
            'is_new' => $isNew,
        ]);
    }
}
