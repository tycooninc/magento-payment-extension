<?php

namespace Sachu\Eaterypayment\Model;

/**
 * Pay In Store payment method model
 */
class PaymentMethod extends \Magento\Payment\Model\Method\AbstractMethod
{
    /**
     * Payment code
     *
     * @var string
     */
    protected $_code = 'eaterypayment';
    protected $_isOffline = false;

    protected $_isGateway = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;

    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;

    protected $_isInitializeNeeded = true;

    /**
     * Authorizes specified amount.
     *
     * @param InfoInterface $payment
     * @param float         $amount
     *
     * @return $this
     *
     * @throws LocalizedException
     */
    public function authorize( \Magento\Payment\Model\InfoInterface $payment, $amount )
    {
        return $this;
    }
}
