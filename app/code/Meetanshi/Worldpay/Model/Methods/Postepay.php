<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Postepay extends Main
{
    protected $_code = 'postepay';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
