<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class AccountController extends AbstractController
{
    #[Route('/profile/doi-mat-khau', name: 'change_password', methods: ['GET', 'POST'])]
    public function changePassword(Request $request, UserPasswordHasherInterface $hasher, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');
        /** @var User $user */
        $user = $this->getUser();

        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('change_password', (string) $request->request->get('_token'))) {
                $this->addFlash('error', 'Phiên làm việc không hợp lệ, vui lòng thử lại.');
                return $this->redirectToRoute('change_password');
            }
            $current = (string) $request->request->get('current');
            $new = (string) $request->request->get('new');
            $confirm = (string) $request->request->get('confirm');

            if (!$hasher->isPasswordValid($user, $current)) {
                $this->addFlash('error', 'Mật khẩu hiện tại không đúng.');
            } elseif (strlen($new) < 8) {
                $this->addFlash('error', 'Mật khẩu mới phải có ít nhất 8 ký tự.');
            } elseif ($new !== $confirm) {
                $this->addFlash('error', 'Mật khẩu nhập lại không khớp.');
            } elseif ($new === $current) {
                $this->addFlash('error', 'Mật khẩu mới phải khác mật khẩu hiện tại.');
            } else {
                $user->setPassword($hasher->hashPassword($user, $new));
                $em->flush();
                $this->addFlash('success', 'Đã đổi mật khẩu thành công.');
                return $this->redirectToRoute('profile');
            }
            return $this->redirectToRoute('change_password');
        }

        return $this->render('homepage/change_password.html.twig');
    }
}
