<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\SanPham;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class QuestionController extends AbstractController
{
    #[Route('/product/{id}/question', name: 'app_question_add', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function ask(SanPham $product, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('question_' . $product->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token không hợp lệ.');
            return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
        }

        $content = trim((string) $request->request->get('content'));
        $name = trim((string) $request->request->get('author_name'));
        if ($this->getUser() && $name === '') {
            $name = $this->getUser()->getUserIdentifier();
        }
        if ($content === '' || $name === '') {
            $this->addFlash('error', 'Vui lòng nhập tên và câu hỏi.');
            return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
        }

        $q = (new Question())
            ->setProduct($product)
            ->setAuthorName(mb_substr($name, 0, 80))
            ->setContent($content)
            ->setCreatedAt(new \DateTime());
        $em->persist($q);
        $em->flush();

        $this->addFlash('success', 'Đã gửi câu hỏi. Chúng tôi sẽ trả lời sớm nhất.');

        return $this->redirectToRoute('app_detailsp', ['id' => $product->getId()]);
    }
}
