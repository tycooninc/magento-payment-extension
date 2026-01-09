<?php
namespace Sachu\Eaterypayment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Sachu\Eaterypayment\Util\Helper;

class Start extends Action
{
    protected $checkoutSession;
    protected $scopeConfig;
    protected $storeManager;
    protected $checkoutUrl;
    protected $callbackUrl;
    protected $notificationUrl;
    protected $appId;
    protected $merchantNo;
    protected $merchantSecret;
    protected $orderRepository;
    protected $transactionMethod;

    protected $payment_icon;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        StoreManagerInterface $storeManager,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $store = $this->storeManager->getStore();
        $baseUrl = $store->getBaseUrl();
        $this->checkoutUrl  = str_replace('\/', '/', $this->scopeConfig->getValue(
                'payment/eaterypayment/gateway_url',
                ScopeInterface::SCOPE_STORE
            )."checkout/payment");
        $this->callbackUrl  = str_replace('\/', '/', $baseUrl."eaterypayment/payment/callback");

        $this->notificationUrl  = str_replace('\/', '/', $baseUrl."eaterypayment/payment/notify");

        $this->appId = $this->scopeConfig->getValue(
            'payment/eaterypayment/appid',
            ScopeInterface::SCOPE_STORE
        );

        $this->payment_icon = "/media/custom_folder/images/".$this->scopeConfig->getValue(
            'payment/eaterypayment/custom_file_upload',
            ScopeInterface::SCOPE_STORE
        );

        $this->transactionMethod = $this->scopeConfig->getValue(
            'payment/eaterypayment/transactionMethod',
            ScopeInterface::SCOPE_STORE
        );

        $this->merchantNo = $this->scopeConfig->getValue(
            'payment/eaterypayment/merchant_number',
            ScopeInterface::SCOPE_STORE
        );
        $this->merchantSecret = $this->scopeConfig->getValue(
            'payment/eaterypayment/merchant_secret',
            ScopeInterface::SCOPE_STORE
        );
        $this->orderRepository = $orderRepository;
    }

    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();

        if (!$order->getId()) {
            $this->_redirect('checkout/cart');
            return;
        }

        $order_items = $order->getAllVisibleItems();

        $product_infos = [];

        foreach ($order_items as $item){
            $product_infos[] = [
                'productName' => $item->getName(),
                'currency' => $order->getBaseCurrency()->getCode(),
                'price' => $item->getPrice(),
                'sku' => $item->getSku(),
                'quantity' => strval(intval($item->getQtyOrdered())),
                'productLink' => str_replace('\/', '/', $item->getProduct()->getProductUrl())
            ];
        }

        $grandTotal = $order->getGrandTotal();

        $sampleJson = $this->submitSampleJson();

        $shippingAddress = $order->getShippingAddress();
        if (!$shippingAddress) {
            $shippingAddress = $order->getBillingAddress();
        }

        $streetArray = $shippingAddress->getStreet();
        $fullStreet = implode(' ', $streetArray);

        $sampleJson = str_replace('123456', $this->appId, $sampleJson);
        $sampleJson = str_replace('https://domain/callback', $this->callbackUrl, $sampleJson);
        $sampleJson = str_replace('https://domain/notification', $this->notificationUrl, $sampleJson);
        $sampleJson = str_replace('trxid', $order->getId().'-'.time(), $sampleJson);
        $sampleJson = str_replace('8.88', $grandTotal, $sampleJson);
        $sampleJson = str_replace($order->getRemoteIp(), '127.0.0.1', $sampleJson);
        $sampleJson = str_replace('Gross', $shippingAddress->getFirstname(), $sampleJson);
        $sampleJson = str_replace('Schmidt', $shippingAddress->getLastname(), $sampleJson);
        $sampleJson = str_replace('USD', $order->getBaseCurrency()->getCode(), $sampleJson);
        $sampleJson = str_replace('CA', $shippingAddress->getCountryId(), $sampleJson);
        $sampleJson = str_replace('kael@gmail.com', $shippingAddress->getEmail(), $sampleJson);
        $sampleJson = str_replace('Longueuil', $shippingAddress->getCity(), $sampleJson);
        $sampleJson = str_replace('450-928-5752', $shippingAddress->getTelephone(), $sampleJson);
        $sampleJson = str_replace('J4H 1M3', $shippingAddress->getPostcode(), $sampleJson);
        $sampleJson = str_replace('3226 rue Saint-Charles', $fullStreet, $sampleJson);
        $sampleJson = str_replace('Defaulte', $this->transactionMethod, $sampleJson);


        $state = $shippingAddress->getRegion();

        if(isset($state)){
            $sampleJson = str_replace('Quebec', $state, $sampleJson);
        }else{
            $sampleJson = str_replace('Quebec', '', $sampleJson);
        }

        $sampleJson = str_replace('productArrayInfo', json_encode($product_infos, JSON_UNESCAPED_SLASHES), $sampleJson);

        $response = Helper::sendRequestWithBasicAuth($this->checkoutUrl, $sampleJson, $this->merchantNo, $this->merchantSecret);

        $response = json_decode($response,true);

        $uniqueId = $response['uniqueId'];

        $orderId = $order->getId();

        $orderRepo = $this->orderRepository->get($orderId);
        $payment = $orderRepo->getPayment();
        $payment->setAdditionalInformation('uniqueId', $uniqueId);
        $this->orderRepository->save($orderRepo);

        if(isset($response['redirectUrl'])){
            header('Location: '.$response['redirectUrl']);exit;
        }

    }

    function submitSampleJson(): string
    {
        $json = '{
        "transactionType": "Sale",
    "transactionId": "trxid",
    "currency": "USD",
    "amount": "8.88",
    "appId": "123456",
    "transactionMethod":"Defaulte",
    "callbackUrl": "https://domain/callback",
    "notificationUrl": "https://domain/notification",
    "transactionIp": "127.0.0.1",
    "productInfos": productArrayInfo,
    "billingAddress": {
        "firstName": "Gross",
        "lastName": "Schmidt",
        "country": "CA",
        "email": "kael@gmail.com",
        "city": "Longueuil",
        "address": "3226 rue Saint-Charles",
        "phone": "450-928-5752",
        "state": "Quebec",
        "zipCode": "J4H 1M3"
    },
    "shippingAddress": {
            "firstName": "Gross",
        "lastName": "Schmidt",
        "country": "CA",
        "email": "kael@gmail.com",
        "city": "Longueuil",
        "address": "3226 rue Saint-Charles",
        "phone": "450-928-5752",
        "state": "Quebec",
        "zipCode": "J4H 1M3"
    },
    "browser": {
            "userAgent": "Mozilla/5.0 (Windows NT 6.1; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.24 Safari/537.36 Edg/83.0.478.18",
            "referer": "https://www.domain.com"
    },
    "extension": {}
}';

        return $json;
    }
}
