<?php

namespace Meetanshi\Worldpay\Controller\Threeds;

use Magento\Framework\View\LayoutFactory;
use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Framework\App\Action\Action;

abstract class Threeds extends Action
{
    protected $modelSession;
    protected $viewLayoutFactory;
    protected $wordpayPaymentsCard;
    protected $urlBuilder;
    protected $checkoutSession;
    protected $orderFactory;
    protected $orderSender;

    public function __construct(Context $context, LayoutFactory $viewLayoutFactory, PaymentHelper $paymentHelper, JsonFactory $resultJsonFactory, OrderFactory $orderFactory, Session $checkoutSession, OrderSender $orderSender)
    {
        $this->viewLayoutFactory = $viewLayoutFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->urlBuilder = $context->getUrl();
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->wordpayPaymentsCard = $paymentHelper->getMethodInstance('card');
        parent::__construct($context);
    }
}
