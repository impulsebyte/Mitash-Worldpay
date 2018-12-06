<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Paysafecard extends Main
{
    protected $_code = 'paysafecard';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
