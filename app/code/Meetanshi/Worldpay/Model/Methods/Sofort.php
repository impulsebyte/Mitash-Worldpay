<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Sofort extends Main
{
    protected $_code = 'sofort';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
