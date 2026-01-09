<?php
namespace Sachu\Eaterypayment\Block\Adminhtml\Order\View\Tab;

class Info extends \Magento\Sales\Block\Adminhtml\Order\View\Tab\Info
{
    /**
     * Get custom data to display in the template.
     * * @return string
     */
    public function getCardNumber():string
    {
        $order = $this->getOrder();
        if (!$order->getPayment()->getAdditionalInformation('cardNumber')) {
            return '';
        }
        return $order->getPayment()->getAdditionalInformation('cardNumber');
    }

    /**
     * Get another piece of data, maybe a configuration setting
     * * @return bool
     */
    public function shouldShowBlock()
    {
        return true;
    }
}
