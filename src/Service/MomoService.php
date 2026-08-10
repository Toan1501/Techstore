<?php

namespace App\Service;

use App\Entity\Order;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * MoMo (sandbox) payment helper — MoMo Payment Gateway API v2, requestType "captureWallet".
 * Docs: https://developers.momo.vn/v3/vi/docs/payment/api/wallet/onetime
 *
 * Flow: POST a signed JSON request to the create endpoint → MoMo returns a `payUrl`
 * to redirect the customer to. The result comes back via redirectUrl (browser) and
 * ipnUrl (server-to-server, authoritative), both signed with HMAC-SHA256.
 */
class MomoService
{
    public function __construct(
        #[Autowire('%env(MOMO_PARTNER_CODE)%')] private string $partnerCode,
        #[Autowire('%env(MOMO_ACCESS_KEY)%')] private string $accessKey,
        #[Autowire('%env(MOMO_SECRET_KEY)%')] private string $secretKey,
        #[Autowire('%env(MOMO_ENDPOINT)%')] private string $endpoint,
        private UrlGeneratorInterface $urlGenerator,
        private HttpClientInterface $httpClient,
        private LoggerInterface $logger,
    ) {
    }

    public function isConfigured(): bool
    {
        return $this->partnerCode !== '' && $this->accessKey !== '' && $this->secretKey !== '';
    }

    /**
     * Ask MoMo to create a payment and return the redirect `payUrl`.
     * Returns null if MoMo rejects the request (caller should fall back gracefully).
     */
    public function createPaymentUrl(Order $order, Request $request): ?string
    {
        $redirectUrl = $this->urlGenerator->generate('app_payment_momo_return', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $ipnUrl = $this->urlGenerator->generate('app_payment_momo_ipn', [], UrlGeneratorInterface::ABSOLUTE_URL);

        // orderId must be unique per request; keep the numeric order id as its prefix
        // so callbacks can recover it with a plain (int) cast.
        $orderId = $order->getId() . '_' . date('YmdHis');
        $requestId = $orderId;
        $amount = (string) (int) round((float) $order->getTotalPrice());
        $orderInfo = 'Thanh toan don hang #' . $order->getId();
        $extraData = '';
        $requestType = 'captureWallet';

        $rawSignature = 'accessKey=' . $this->accessKey
            . '&amount=' . $amount
            . '&extraData=' . $extraData
            . '&ipnUrl=' . $ipnUrl
            . '&orderId=' . $orderId
            . '&orderInfo=' . $orderInfo
            . '&partnerCode=' . $this->partnerCode
            . '&redirectUrl=' . $redirectUrl
            . '&requestId=' . $requestId
            . '&requestType=' . $requestType;
        $signature = hash_hmac('sha256', $rawSignature, $this->secretKey);

        $payload = [
            'partnerCode' => $this->partnerCode,
            'accessKey' => $this->accessKey,
            'requestId' => $requestId,
            'amount' => $amount,
            'orderId' => $orderId,
            'orderInfo' => $orderInfo,
            'redirectUrl' => $redirectUrl,
            'ipnUrl' => $ipnUrl,
            'extraData' => $extraData,
            'requestType' => $requestType,
            'signature' => $signature,
            'lang' => 'vi',
        ];

        try {
            $response = $this->httpClient->request('POST', $this->endpoint, [
                'json' => $payload,
                'timeout' => 15,
            ]);
            $data = $response->toArray(false);
        } catch (\Throwable $e) {
            $this->logger->error('MoMo create payment failed: ' . $e->getMessage());
            return null;
        }

        if (($data['resultCode'] ?? -1) == 0 && !empty($data['payUrl'])) {
            return $data['payUrl'];
        }

        $this->logger->error('MoMo create payment rejected', $data);
        return null;
    }

    /**
     * Validate the signature of a MoMo callback (redirect return or IPN).
     *
     * @param array<string,mixed> $p full request query/params
     */
    public function isValidSignature(array $p): bool
    {
        if (empty($p['signature'])) {
            return false;
        }

        $raw = 'accessKey=' . $this->accessKey
            . '&amount=' . ($p['amount'] ?? '')
            . '&extraData=' . ($p['extraData'] ?? '')
            . '&message=' . ($p['message'] ?? '')
            . '&orderId=' . ($p['orderId'] ?? '')
            . '&orderInfo=' . ($p['orderInfo'] ?? '')
            . '&orderType=' . ($p['orderType'] ?? '')
            . '&partnerCode=' . ($p['partnerCode'] ?? '')
            . '&payType=' . ($p['payType'] ?? '')
            . '&requestId=' . ($p['requestId'] ?? '')
            . '&responseTime=' . ($p['responseTime'] ?? '')
            . '&resultCode=' . ($p['resultCode'] ?? '')
            . '&transId=' . ($p['transId'] ?? '');
        $computed = hash_hmac('sha256', $raw, $this->secretKey);

        return hash_equals($computed, (string) $p['signature']);
    }

    /** A callback is a successful payment when resultCode is 0. */
    public function isSuccess(array $p): bool
    {
        return (int) ($p['resultCode'] ?? -1) === 0;
    }

    /** Recover the internal order id from MoMo's unique orderId ("{id}_{timestamp}"). */
    public function orderIdFromCallback(array $p): int
    {
        return (int) ($p['orderId'] ?? 0);
    }
}
