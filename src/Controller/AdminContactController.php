<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/contacts')]
class AdminContactController extends AbstractController
{
    #[Route('', name: 'admin_contact_list', methods: ['GET'])]
    public function list(ContactRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/contact/list.html.twig', [
            'items' => $repo->findBy([], ['id' => 'DESC']),
        ]);
    }

    #[Route('/{id}/delete', name: 'admin_contact_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Contact $contact, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if ($this->isCsrfTokenValid('delete_contact_' . $contact->getId(), $request->request->get('_token'))) {
            $em->remove($contact);
            $em->flush();
            $this->addFlash('success', 'Đã xóa tin nhắn.');
        }

        return $this->redirectToRoute('admin_contact_list');
    }
}
