<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Przelewy24 extends Main
{
    protected $_code = 'przelewy24';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
