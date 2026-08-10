<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/users')]
class AdminUserController extends AbstractController
{
    #[Route('', name: 'admin_user_list', methods: ['GET'])]
    public function list(UserRepository $repo): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        return $this->render('admin/user/list.html.twig', [
            'items' => $repo->findBy([], ['id' => 'ASC']),
        ]);
    }

    #[Route('/{id}/toggle-admin', name: 'admin_user_toggle', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function toggleAdmin(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('toggle_user_' . $user->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_user_list');
        }
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Không thể tự đổi quyền của chính mình.');
            return $this->redirectToRoute('admin_user_list');
        }

        $user->setRoles(in_array('ROLE_ADMIN', $user->getRoles(), true) ? [] : ['ROLE_ADMIN']);
        $em->flush();
        $this->addFlash('success', 'Đã cập nhật quyền của "' . $user->getUsername() . '".');

        return $this->redirectToRoute('admin_user_list');
    }

    #[Route('/{id}/delete', name: 'admin_user_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');
        if (!$this->isCsrfTokenValid('delete_user_' . $user->getId(), $request->request->get('_token'))) {
            return $this->redirectToRoute('admin_user_list');
        }
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Không thể xóa tài khoản đang đăng nhập.');
            return $this->redirectToRoute('admin_user_list');
        }

        try {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'Đã xóa người dùng.');
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Không thể xóa: người dùng còn đơn hàng hoặc đánh giá liên quan.');
        }

        return $this->redirectToRoute('admin_user_list');
    }
}
