<?php

namespace Meetanshi\Worldpay\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;

class SendMailOnOrderSuccess implements ObserverInterface
{
    protected $orderModel;

    protected $orderSender;

    protected $checkoutSession;

    public function __construct(OrderFactory $orderModel, OrderSender $orderSender, Session $checkoutSession
    )
    {
        $this->orderModel = $orderModel;
        $this->orderSender = $orderSender;
        $this->checkoutSession = $checkoutSession;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $order = $observer->getEvent()->getOrder()->save();
        $payment = $order->getPayment()->getMethodInstance()->getCode();
        try {
            if ($payment == 'card') {
                $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
                $objectManager->create('Magento\Sales\Model\OrderNotifier')->notify($order);
            }
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }
}
