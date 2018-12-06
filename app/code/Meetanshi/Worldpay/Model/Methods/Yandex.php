<?php
namespace Meetanshi\Worldpay\Model\Methods;

class Yandex extends Main
{
    protected $_code = 'yandex';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
