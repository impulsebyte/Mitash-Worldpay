<?php

namespace Meetanshi\Worldpay\Block\Adminhtml;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\Factory;

class Sitecode extends AbstractFieldArray
{
    protected $currencyRenderer = null;

    protected $settlementCurrencyRenderer = null;

    protected $_elementFactory;

    public function __construct(Context $context, Factory $elementFactory, array $data = [])
    {
        $this->_elementFactory = $elementFactory;
        parent::__construct($context, $data);
    }

    protected function _construct()
    {
        $this->addColumn('currency', ['label' => __('Acceptance Currency'), 'renderer' => $this->getCurrencyRenderer()]);
        $this->addColumn('settlement_currency', ['label' => __('Settlement Currency'), 'renderer' => $this->getSettlementCurrencyRenderer()]);
        $this->addColumn('site_code', ['label' => __('Sitecode')]);
        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
        parent::_construct();
    }

    protected function getCurrencyRenderer()
    {
        if (!$this->currencyRenderer) {
            $this->currencyRenderer = $this->getLayout()->createBlock(
                'Meetanshi\Worldpay\Block\Adminhtml\Currencies',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->currencyRenderer;
    }

    protected function getSettlementCurrencyRenderer()
    {
        if (!$this->settlementCurrencyRenderer) {
            $this->settlementCurrencyRenderer = $this->getLayout()->createBlock(
                'Meetanshi\Worldpay\Block\Adminhtml\SettlementCurrencies',
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }
        return $this->settlementCurrencyRenderer;
    }

    protected function _prepareArrayRow(\Magento\Framework\DataObject $row)
    {
        $options = [];
        $currency = $row->getCurrency();
        if ($currency) {
            $options['option_' . $this->getCurrencyRenderer()->calcOptionHash($currency)] = 'selected="selected"';
        }

        $settlementCurrency = $row->getSettlementCurrency();

        if ($settlementCurrency) {
            $options['option_' . $this->getSettlementCurrencyRenderer()->calcOptionHash($settlementCurrency)] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }
}
