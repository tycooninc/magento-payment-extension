<?php
namespace Sachu\Eaterypayment\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Checkout\Model\Session;

class Callback extends Action
{
    protected $checkoutSession;

    public function __construct(
        Context $context,
        Session $checkoutSession
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
    }

    public function execute()
    {
        $this->_redirect('checkout/onepage/success');
    }
}
