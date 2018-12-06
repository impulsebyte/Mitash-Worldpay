<?php

namespace Meetanshi\Worldpay\Block\Adminhtml;

use Magento\Framework\View\Element\Html\Select;
use Magento\Framework\View\Element\Context;
use Magento\Config\Model\Config\Source\Locale\Currency;

class Currencies extends Select
{
    public function __construct(Context $context, Currency $currencies, array $data = [])
    {
        parent::__construct($context, $data);
        $this->currencies = $currencies;
    }

    public function _toHtml()
    {
        if (!$this->getOptions()) {
            $allCurrencies = $this->currencies->toOptionArray();
            foreach ($allCurrencies as $currency) {
                $this->addOption($currency['value'], $currency['value']);
            }
        }
        return parent::_toHtml();
    }

    public function setInputName($value)
    {
        return $this->setName($value);
    }
}
