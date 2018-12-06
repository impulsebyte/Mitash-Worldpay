<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Mistercash extends Main
{
    protected $_code = 'mistercash';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
