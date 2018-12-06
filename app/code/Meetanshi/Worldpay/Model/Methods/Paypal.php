<?php

namespace Meetanshi\Worldpay\Model\Methods;

class Paypal extends Main
{
    protected $_code = 'paypal4';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
