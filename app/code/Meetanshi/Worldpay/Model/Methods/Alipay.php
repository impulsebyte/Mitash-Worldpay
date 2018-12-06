<?php

namespace Meetanshi\Worldpay\Model\Methods;

class Alipay extends Main
{
    protected $_code = 'alipay';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    //protected $_formBlockType = 'worldpay/payment_alipayForm';
    protected $_infoBlockType = 'Meetanshi\Worldpay\Block\Form\Info';
    protected $_isGateway = true;
}
