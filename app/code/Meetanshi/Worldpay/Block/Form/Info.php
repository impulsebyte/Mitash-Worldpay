<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Meetanshi\Worldpay\Block\Form;

use Magento\Framework\Phrase;
use Magento\Payment\Block\ConfigurableInfo;

/**
 * Class Info
 */
class Info extends ConfigurableInfo
{
    protected $_template = 'Meetanshi_Worldpay::form/info.phtml';
    /**
     * Returns label
     *
     * @param string $field
     * @return Phrase
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function getLabel($field)
    {
        switch ($field) {
            case 'payment_type':
                return __('Payment Type');
            case 'amount':
                return __('Authorization Amount');
            case 'currencyCode':
                return __('Currency');
            case 'cardType':
                return __('Card Type');
            case 'maskedCardNumber':
                return __('Card number');
            case 'expiryMonth':
                return __('Expiration Month');
            case 'expiryYear':
                return __('Expiration Year');
            case 'paymentStatus':
                return __('Payment Status');
            case 'payment_token':
                return __('Payment Token');
            case 'avsResultCode':
                return __('AVS Code');
            case 'cvcResultCode':
                return __('CVC Code');
            case 'worldpayOrderCode':
                return __('Order Code');
            default:
                return parent::getLabel($field);
        }
    }
}
