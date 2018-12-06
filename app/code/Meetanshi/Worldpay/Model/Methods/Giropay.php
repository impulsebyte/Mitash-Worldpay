<?php

namespace Meetanshi\Worldpay\Model\Methods;

class Giropay extends Main
{
    protected $_code = 'giropay';
    protected $_canUseInternal = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canRefund = true;
    protected $_isGateway = true;
}
