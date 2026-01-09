<?php
namespace Sachu\Eaterypayment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface; // CRITICAL FOR POST
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Serialize\Serializer\Json; // To decode JSON safely
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface; // To verify it worked
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;

class Notify extends Action implements CsrfAwareActionInterface
{
    protected $resultJsonFactory;
    protected $logger;
    protected $scopeConfig;
    protected $orderFactory;
    protected $orderRepository;
    protected $merchantSecret;

    public function __construct(
        Context $context,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger,
        OrderFactory $orderFactory,
        OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->scopeConfig = $scopeConfig;
        $this->orderFactory = $orderFactory;
        $this->orderRepository = $orderRepository;
        $this->merchantSecret = $this->scopeConfig->getValue(
            'payment/eaterypayment/merchant_secret',
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        try {
            $content = $this->getRequest()->getContent();

            if ($content) {

                $data = json_decode($content, true);

                $sign = $this->mapKeys($data);

                if ($sign == $data["sign"]) {
                    $uniqueId = $data['uniqueId'];
                    $transactionId = $data['transactionId'];
                    $transactionType = $data['transactionType'];
                    $transactionCardNumber = $data['transactionCardNumber'];
                    $code = $data['code'];

                    if(str_contains($transactionId, '-') && $transactionType == 'Sale' && $code == '100'){

                        $orderArray = explode('-', $transactionId);
                        $orderIds = $orderArray[0];

                        $order = $this->orderRepository->get($orderIds);

                        $payment = $order->getPayment();
                        $payment->setAdditionalInformation('cardNumber', $transactionCardNumber);

                        $order->setState(Order::STATE_PROCESSING);
                        $order->setStatus(Order::STATE_PROCESSING);

                        $order->addCommentToStatusHistory('Payment confirmed via Callback API.');
                        $this->orderRepository->save($order);
                    }
                } else {
                    throw new \Exception('Signature mismatch');
                }

            } else {
                $data = $this->getRequest()->getParams();
            }

            $this->logger->info('Payment Callback Received:', ['data' => $data]);

            $result = $this->resultJsonFactory->create();
            return $result->setData(['success' => true, 'message' => 'Received']);

        } catch (\Exception $e) {

        }
    }

    /**
     * @inheritDoc
     * Bypasses the CSRF token check.
     * Required for external systems (Stripe, PayPal, etc.) to POST to this URL.
     */
    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    /**
     * @inheritDoc
     * Return true to allow the request to proceed without a CSRF token.
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    function mapKeys($map)
    {
        if ($map["isTest"] == true) {
            $map["isTest"] = "true";
        } else {
            $map["isTest"] = "false";
        }
        unset($map["sign"]);
        $values = array_filter($map, function ($value) {
            return !empty($value);
        });
        ksort($values);
        $buffer = implode("", $values);
        $merchantSecret = $this->merchantSecret;

        $buffer .= $merchantSecret;

        $sign = hash("sha256", $buffer);

        return $sign;
    }
}
