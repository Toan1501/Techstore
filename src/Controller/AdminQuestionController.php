<?php

namespace App\Controller;

use App\Entity\Question;
use App\Repository\QuestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/questions')]
class AdminQuestionController extends AbstractController
{
    #[Route('', name: 'admin_question_list', methods: ['GET'])]
    public function list(QuestionRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/question/list.html.twig', ['items' => $repo->findAllForAdmin()]);
    }

    #[Route('/{id}/answer', name: 'admin_question_answer', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function answer(Question $question, Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('answer_question_' . $question->getId(), $req->request->get('_token'))) {
            $answer = trim((string) $req->request->get('answer'));
            $question->setAnswer($answer !== '' ? $answer : null);
            $question->setAnsweredAt($answer !== '' ? new \DateTime() : null);
            $em->flush();
            $this->addFlash('success', 'Đã lưu câu trả lời.');
        }

        return $this->redirectToRoute('admin_question_list');
    }

    #[Route('/{id}/delete', name: 'admin_question_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Question $question, Request $req, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete_question_' . $question->getId(), $req->request->get('_token'))) {
            $em->remove($question);
            $em->flush();
            $this->addFlash('success', 'Đã xóa câu hỏi.');
        }

        return $this->redirectToRoute('admin_question_list');
    }
}
