<?php
namespace Meetanshi\Worldpay\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Escaper;
use Magento\Payment\Helper\Data as PaymentHelper;

class ConfigProvider implements ConfigProviderInterface
{
    protected $methodCodes = [
        'card'
    ];

    protected $methods = [];

    protected $escaper;

    protected $config;

    public function __construct(
        PaymentHelper $paymentHelper,
        Escaper $escaper,
        Config $config
    ) {
        $this->escaper = $escaper;
        $this->config = $config;
        foreach ($this->methodCodes as $code) {
            $this->methods[$code] = $paymentHelper->getMethodInstance($code);
        }
    }

    public function getConfig()
    {
        $outConfig = [];
        $outConfig['payment']['worldpay']['client_key'] = $this->config->getClientKey();
        $outConfig['payment']['worldpay']['save_card'] = $this->config->saveCard();

        if ($this->config->saveCard()) {
            $outConfig['payment']['worldpay']['saved_cards'] = $this->methods['card']->getSavedCards();
        }

        $outConfig['payment']['worldpay']['language_code'] = $this->config->getLanguageCode();
        $outConfig['payment']['worldpay']['country_code'] = $this->config->getShopCountryCode();

        $outConfig['payment']['worldpay']['threeds_enabled'] = filter_var($this->config->threeDSEnabled(), FILTER_VALIDATE_BOOLEAN);

        if ($this->config->threeDSEnabled()) {
             $outConfig['payment']['worldpay']['ajax_generate_order_url'] = $this->methods['card']->getGenerateOrder3DSUrl();
        }

        foreach ($this->methodCodes as $code) {
            if ($this->methods[$code]->isAvailable()) {
                $outConfig['payment']['worldpay']['redirect_url'] = $this->getMethodRedirectUrl($code);
            }
        }
        return $outConfig;
    }


    public function getMethodRedirectUrl($code)
    {
        return $this->methods[$code]->getOrderPlaceRedirectUrl();
    }
}
