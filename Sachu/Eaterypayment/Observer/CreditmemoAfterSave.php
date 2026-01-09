<?php

namespace Sachu\Eaterypayment\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Sachu\Eaterypayment\Util\Helper;

class CreditmemoAfterSave implements ObserverInterface
{
    protected $orderRepository;
    protected $merchantNo;
    protected $merchantSecret;
    protected $gatewayUrl;
    protected $scopeConfig;
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->orderRepository = $orderRepository;
        $this->scopeConfig = $scopeConfig;
        $this->merchantNo = $this->scopeConfig->getValue(
            'payment/eaterypayment/merchant_number',
            ScopeInterface::SCOPE_STORE
        );
        $this->merchantSecret = $this->scopeConfig->getValue(
            'payment/eaterypayment/merchant_secret',
            ScopeInterface::SCOPE_STORE
        );

        $this->gatewayUrl = $this->scopeConfig->getValue(
            'payment/eaterypayment/gateway_url',
            ScopeInterface::SCOPE_STORE
        );
    }
    public function execute(Observer $observer)
    {
        /** @var \Magento\Sales\Model\Order\Creditmemo $creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();

        $orderId = $creditmemo->getOrderId();

        $amountRefunded = $creditmemo->getGrandTotal();

        $order = $this->orderRepository->get($orderId);

        $uniqueId = $order->getPayment()->getAdditionalInformation('uniqueId');

        if (!$uniqueId) {
            return;
        }

        Helper::sendRequestWithBasicAuth($this->gatewayUrl . 'api/refund', '{"uniqueId": "' . $uniqueId . '", "amount": "' . $amountRefunded . '", "remark": "Refunded by admin", "refundTransactionId": '.$orderId.'}', $this->merchantNo, $this->merchantSecret);

    }

    function sendRequestWithBasicAuth($url, $data)
    {
        $authorization = base64_encode($this->merchantNo . ":" . $this->merchantSecret);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Basic ' . $authorization
        ));
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

}
