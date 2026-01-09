<?php

namespace Sachu\Eaterypayment\Observer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Sachu\Eaterypayment\Util\Helper;

class ShipmentObserver implements ObserverInterface
{

    protected $scopeConfig;

    protected $merchantNo;

    protected $merchantSecret;

    protected $gateway_url;
    protected $orderRepository;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        OrderRepositoryInterface $orderRepository
    )
    {
        $this->scopeConfig = $scopeConfig;
        $this->merchantNo = $this->scopeConfig->getValue(
            'payment/eaterypayment/merchant_number',
            ScopeInterface::SCOPE_STORE
        );
        $this->merchantSecret = $this->scopeConfig->getValue(
            'payment/eaterypayment/merchant_secret',
            ScopeInterface::SCOPE_STORE
        );

        $this->gateway_url = $this->scopeConfig->getValue(
            'payment/eaterypayment/gateway_url',
            ScopeInterface::SCOPE_STORE
        );

        $this->orderRepository = $orderRepository;
    }

    public function execute(Observer $observer)
    {
        $shipment = $observer->getEvent()->getShipment();

        $orderId = $shipment->getOrder()->getIncrementId();

        $tracks = $shipment->getAllTracks();

        foreach ($tracks as $track) {
            $carrierCode = $track->getCarrierCode();
            $carrierTitle = $track->getTitle();
            $trackingNumber = $track->getTrackNumber();

            $order = $this->orderRepository->get($orderId);

            $uniqueId = $order->getPayment()->getAdditionalInformation('uniqueId');

            if($carrierCode == "custom"){
                Helper::sendRequestWithBasicAuth($this->gateway_url."upload/trackingNumber", '{"uniqueId":"'.$uniqueId.'","company":"'.$carrierTitle.'","trackingNumber":"'.$trackingNumber.'"}', $this->merchantNo, $this->merchantSecret);
            }

        }
    }
}

