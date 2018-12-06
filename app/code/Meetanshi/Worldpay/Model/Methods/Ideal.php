<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Ideal extends Main
{
    protected $_code = 'ideal';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
