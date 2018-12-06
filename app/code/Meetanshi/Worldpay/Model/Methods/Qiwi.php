<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Qiwi extends Main
{
    protected $_code = 'qiwi';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
