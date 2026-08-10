<?php

namespace App\Service;

use App\Entity\Order;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * VNPay (sandbox) payment helper — VNPay API v2.1.0.
 * Docs: https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/pay.html
 */
class VnpayService
{
    public function __construct(
        #[Autowire('%env(VNP_TMN_CODE)%')] private string $tmnCode,
        #[Autowire('%env(VNP_HASH_SECRET)%')] private string $hashSecret,
        #[Autowire('%env(VNP_URL)%')] private string $payUrl,
        private UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->tmnCode !== '' && $this->hashSecret !== '';
    }

    /** Build the VNPay redirect URL for an order. */
    public function createPaymentUrl(Order $order, Request $request): string
    {
        $returnUrl = $this->urlGenerator->generate('app_payment_vnpay_return', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $data = [
            'vnp_Version' => '2.1.0',
            'vnp_Command' => 'pay',
            'vnp_TmnCode' => $this->tmnCode,
            'vnp_Amount' => (int) round((float) $order->getTotalPrice() * 100),
            'vnp_CurrCode' => 'VND',
            'vnp_TxnRef' => (string) $order->getId(),
            'vnp_OrderInfo' => 'Thanh toan don hang #' . $order->getId(),
            'vnp_OrderType' => 'other',
            'vnp_Locale' => 'vn',
            'vnp_ReturnUrl' => $returnUrl,
            'vnp_IpAddr' => $request->getClientIp() ?: '127.0.0.1',
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_ExpireDate' => date('YmdHis', strtotime('+15 minutes')),
        ];

        ksort($data);
        $hashData = '';
        $query = '';
        $i = 0;
        foreach ($data as $key => $value) {
            $hashData .= ($i === 1 ? '&' : '') . urlencode($key) . '=' . urlencode($value);
            $i = 1;
            $query .= urlencode($key) . '=' . urlencode($value) . '&';
        }

        $secureHash = hash_hmac('sha512', $hashData, $this->hashSecret);

        return $this->payUrl . '?' . $query . 'vnp_SecureHash=' . $secureHash;
    }

    /**
     * Validate the signature of a VNPay callback (return or IPN).
     *
     * @param array<string,string> $params full request query/params
     */
    public function isValidSignature(array $params): bool
    {
        if (empty($params['vnp_SecureHash'])) {
            return false;
        }
        $received = $params['vnp_SecureHash'];
        unset($params['vnp_SecureHash'], $params['vnp_SecureHashType']);
        ksort($params);

        $hashData = '';
        $i = 0;
        foreach ($params as $key => $value) {
            $hashData .= ($i === 1 ? '&' : '') . urlencode($key) . '=' . urlencode($value);
            $i = 1;
        }
        $computed = hash_hmac('sha512', $hashData, $this->hashSecret);

        return hash_equals($computed, $received);
    }

    /** A callback is a successful payment when both codes are '00'. */
    public function isSuccess(array $params): bool
    {
        return ($params['vnp_ResponseCode'] ?? null) === '00'
            && ($params['vnp_TransactionStatus'] ?? null) === '00';
    }
}
