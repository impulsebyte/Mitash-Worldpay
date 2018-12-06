<?php

namespace Meetanshi\Worldpay\Block\Form;

use Magento\Payment\Block\Form as PaymentForm;
use Magento\Framework\View\Element\Template\Context;
use Meetanshi\Worldpay\Model\Config;
use Magento\Payment\Helper\Data;

class Card extends PaymentForm
{
    protected $_template = 'Meetanshi_Worldpay::form/card.phtml';

    private $worldpayPaymentsCard;

    public function __construct(Context $context, Config $config, Data $paymentHelper, array $data = [])
    {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->worldpayPaymentsCard = $paymentHelper->getMethodInstance('card');
    }

    public function getClientKey()
    {
        return $this->config->getClientKey();
    }

    public function isSaveCard()
    {
        return $this->config->saveCard();
    }

    public function getSavedCards()
    {
        return $this->worldpayPaymentsCard->getSavedCards();
    }
}
