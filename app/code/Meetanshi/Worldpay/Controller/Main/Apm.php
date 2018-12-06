<?php

namespace Meetanshi\Worldpay\Controller\Main;

use Magento\Payment\Helper\Data as PaymentHelper;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Meetanshi\Worldpay\Model\Methods\Main;
use Magento\Sales\Model\OrderFactory;
use Magento\Checkout\Model\Session;
use Magento\Checkout\Helper\Data;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;

abstract class Apm extends Action
{
    protected $customerSession;
    protected $checkoutSession;
    protected $resultJsonFactory;
    protected $orderFactory;
    protected $methods = [];
    protected $wordpayPaymentsCard;
    protected $methodCodes = [
        'paypal4',
        'giropay',
        'ideal',
        'alipay',
        'mistercash',
        'przelewy24',
        'paysafecard',
        'postepay',
        'qiwi',
        'sofort',
        'yandex'
    ];

    public function __construct(Context $context, Main $Worldpay, PaymentHelper $paymentHelper, OrderFactory $orderFactory, Session $checkoutSession, Data $checkoutData, JsonFactory $resultJsonFactory, OrderSender $orderSender, $params = [])
    {
        $this->checkoutSession = $checkoutSession;
        $this->orderFactory = $orderFactory;
        $this->orderSender = $orderSender;
        $this->wordpayPaymentsCard = $paymentHelper->getMethodInstance('card');
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }
}
