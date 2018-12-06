<?php

namespace Meetanshi\Worldpay\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Payment\Model\Config as PaymentConfig;
use Magento\Backend\Model\Session\Quote;
use Meetanshi\Worldpay\Model\Config;
use Magento\Payment\Helper\Data;

class Cards extends Template
{
    protected $_template = 'Meetanshi_Worldpay::cards.phtml';

    protected $worldpayPaymentsCard;
    protected $config;
    protected $urlBuilder;
    protected $savedCardFactory;

    public function __construct(Context $context, PaymentConfig $paymentConfig, Config $config, Quote $sessionQuote, Data $paymentHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->urlBuilder = $context->getUrlBuilder();
        $this->worldpayPaymentsCard = $paymentHelper->getMethodInstance('card');
    }

    public function getClientKey()
    {
        return $this->config->getClientKey();
    }

    public function getSavedCards()
    {
        return $this->worldpayPaymentsCard->getSavedCards();
    }

    public function isSaveCard()
    {
        return $this->config->saveCard();
    }

    public function getDeleteUrl($token)
    {
        return $this->urlBuilder->getUrl('worldpay/saved/remove', ['_secure' => true, 'id' => $token]);
    }
}
