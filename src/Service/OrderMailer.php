<?php

namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Sends the order confirmation email. Failures are swallowed and logged so a
 * mailer/SMTP problem never blocks a customer from completing checkout.
 */
class OrderMailer
{
    public function __construct(
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private string $fromAddress = 'no-reply@toanstore.local',
    ) {
    }

    public function sendConfirmation(Order $order): void
    {
        if (!$order->getCustomerEmail()) {
            return;
        }

        try {
            $email = (new TemplatedEmail())
                ->from($this->fromAddress)
                ->to($order->getCustomerEmail())
                ->subject('Toàn Store — Order #' . $order->getId() . ' confirmation')
                ->htmlTemplate('email/order_confirmation.html.twig')
                ->context(['order' => $order]);

            $this->mailer->send($email);
        } catch (\Throwable $e) {
            $this->logger->error('Order confirmation email failed: ' . $e->getMessage());
        }
    }
}
