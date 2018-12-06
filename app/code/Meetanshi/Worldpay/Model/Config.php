<?php

namespace Meetanshi\Worldpay\Model;

use Magento\Store\Model\ScopeInterface;

class Config
{
    protected $_scopeConfigInterface;
    protected $customerSession;
    protected $serialize;
    protected $_storeManager;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $configInterface,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Serialize\Serializer\Json $serialize
    )
    {
        $this->_scopeConfigInterface = $configInterface;
        $this->customerSession = $customerSession;
        $this->sessionQuote = $sessionQuote;
        $this->serialize = $serialize;
        $this->_storeManager = $storeManager;
    }

    public function isAuthorizeOnly($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/card/payment_action', $scope) == 'authorize';
    }

    public function saveCard($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/card/storeonfile', $scope) && ($this->customerSession->isLoggedIn() || $this->sessionQuote->getCustomerId());
    }

    public function threeDSEnabled($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/card/use3ds', $scope);
    }

    public function getClientKey($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        if ( $this->isLiveMode() ) {
            return $this->_scopeConfigInterface->getValue('payment/worldpay/test_client_key', $scope);
        } else {
            return $this->_scopeConfigInterface->getValue('payment/worldpay/live_client_key', $scope);
        }
    }

    public function isLiveMode($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay/mode', $scope);
    }

    public function getServiceKey($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        if ( $this->isLiveMode() ) {
            return $this->_scopeConfigInterface->getValue('payment/worldpay/test_service_key', $scope);
        } else {
            return $this->_scopeConfigInterface->getValue('payment/worldpay/live_service_key', $scope);
        }
    }

    public function getSettlementCurrency($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay/settlement_currency', $scope);
    }

    public function debugMode($code, $scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return !!$this->_scopeConfigInterface->getValue('payment/' . $code . '/debug', $scope);
    }

    public function getPaymentDescription($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay/payment_description', $scope);
    }

    public function getLanguageCode($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay/language_code', $scope);
    }

    public function getShopCountryCode($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        return $this->_scopeConfigInterface->getValue('payment/worldpay/shop_country_code', $scope);
    }

    public function getSiteCodes($scope = ScopeInterface::SCOPE_WEBSITE)
    {
        $sitecodeConfig = $this->_scopeConfigInterface->getValue('payment/worldpay/sitecodes', $scope);

        if ( $sitecodeConfig ) {
            $siteCodes = $this->serialize->unserialize($sitecodeConfig);
            if ( is_array($siteCodes) ) {
                return $siteCodes;
            }
        }

        return false;
    }
}
