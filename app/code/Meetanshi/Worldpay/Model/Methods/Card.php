<?php

namespace Meetanshi\Worldpay\Model\Methods;

use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Meetanshi\Worldpay\Model\Config;

class Card extends Main
{

    protected $_formBlockType = 'Meetanshi\Worldpay\Block\Form\Card';
    protected $_infoBlockType = 'Meetanshi\Worldpay\Block\Form\Info';
    protected $_code = 'card';
    protected $_isGateway = false;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = true;
    protected $_canSaveCc = false;
    protected $_isInitializeNeeded = false;
    protected $backendAuthSession;

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->_debug('Card Assign Data Called');
        $_tmpData = $data->_data;
        $_serializedAdditionalData = $this->serialize->serialize($_tmpData['additional_data']);
        $additionalDataRef = $_serializedAdditionalData;
        $additionalDataRef = $this->serialize->unserialize($additionalDataRef);
        $_paymentToken = $additionalDataRef['paymentToken'];
        $_saveCard = isset($additionalDataRef['saveCard']) ? $additionalDataRef['saveCard'] : false;
        parent::assignData($data);
        $infoInstance = $this->getInfoInstance();

        $infoInstance->setAdditionalInformation('payment_token', $_paymentToken);
        $infoInstance->setAdditionalInformation('save_card', $_saveCard);
        // If token is persistent save in db
        if ($_saveCard && ($this->customerSession->isLoggedIn() || $this->backendAuthSession->isLoggedIn())) {
            if ($this->backendAuthSession->isLoggedIn()) {
                $customerId = $this->sessionQuote->getCustomerId();
            } else {
                $customerId = $this->customerSession->getId();
            }

            $token_exists = $this->savedCardFactory->create()
                ->addFieldToFilter('customer_id', $customerId)
                ->addFieldToFilter('token', $_paymentToken)
                ->getFirstItem();

            if (empty($token_exists['token'])) {
                $model = $this->_objectManager->create('Meetanshi\Worldpay\Model\Cards');
                $model->setData('customer_id', $customerId);
                $model->setData('token', $_paymentToken);
                $model->save();
            }
        }
        return $this;
    }

    public function getSavedCards()
    {
        $this->_debug('Card getSavedCards Called');
        if ($this->backendAuthSession->isLoggedIn()) {
            $customerId = $this->sessionQuote->getCustomerId();
        } else {
            $customerId = $this->customerSession->getId();
        }

        $this->sessionQuote->getCustomerId();
        $tokens = $this->savedCardFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->loadData();
        $worldpay = $this->setupWorldpay();
        $storedCards = [];
        foreach ($tokens as $t) {
            try {
                $cardDetails = $worldpay->getStoredCardDetails($t['token']);
            } catch (\Exception $e) {
                // Delete expired tokens
                if ($e->getCustomCode() == 'TKN_NOT_FOUND') {
                    $t->delete();
                }
            }
            if (isset($cardDetails['maskedCardNumber']) && !empty($t->getToken())) {
                $storedCards[] = [
                    'number' => $cardDetails['maskedCardNumber'],
                    'cardType' => $cardDetails['cardType'],
                    'id' => $t->getId(),
                    'token' => $t->getToken()
                ];
            }
        }
        return $storedCards;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        $this->_debug('Card authorize Called');
        if ($payment->getAdditionalInformation("worldpayOrderCode")) {
            return $this;
        }
        $payment->setAdditionalInformation('payment_type', 'authorize');
        $this->createOrder($payment, $amount, true);
    }

    protected function createOrder(InfoInterface $payment, $amount, $authorize)
    {
        $this->_debug('Worldpay Card: Begin create order');

        if ($payment->getOrder()) {
            $orderId = $payment->getOrder()->getIncrementId();
            $order = $payment->getOrder();
        } else {
            $orderId = $payment->getQuote()->getId();
            $order = $payment->getQuote();
        }

        $infoInstance = $this->getInfoInstance();
        $token = $infoInstance->getAdditionalInformation('payment_token');
        $savedCard = $infoInstance->getAdditionalInformation('saved_card');
        $currency_code = $order->getOrderCurrencyCode();

        $this->createWorldpayOrder($orderId, $payment, $token, $amount, $currency_code, $authorize, false, $order);

        return $this;
    }

    protected function createWorldpayOrder($orderId, $payment, $token, $amount, $currency_code, $authorize, $threeDS, $quote)
    {
        $this->_debug('Card createWorldpayOrder Called');
        $worldpay = $this->setupWorldpay();

        $orderDetails = $this->getSharedOrderDetails($quote, $currency_code);

        try {
            $liveMode = $this->config->isLiveMode();

            $orderType = 'ECOM';

            if ($this->backendAuthSession->isLoggedIn()) {
                $orderType = 'MOTO';
                $threeDS = false;
            }

            if ($threeDS && !$liveMode && $orderDetails['name'] != 'NO 3DS') {
                $orderDetails['name'] = '3D';
            }

            $createOrderRequest = [
                'token' => $token,
                'orderDescription' => $orderDetails['orderDescription'],
                'amount' => $amount * 100,
                'currencyCode' => $orderDetails['currencyCode'],
                'siteCode' => $orderDetails['siteCode'],
                'name' => $orderDetails['name'],
                'orderType' => $orderType,
                'is3DSOrder' => $threeDS,
                'authorizeOnly' => $authorize,
                'billingAddress' => $orderDetails['billingAddress'],
                'deliveryAddress' => $orderDetails['deliveryAddress'],
                'customerOrderCode' => $orderId,
                'settlementCurrency' => $orderDetails['settlementCurrency'],
                'shopperIpAddress' => $orderDetails['shopperIpAddress'],
                'shopperSessionId' => $orderDetails['shopperSessionId'],
                'shopperUserAgent' => $orderDetails['shopperUserAgent'],
                'shopperAcceptHeader' => $orderDetails['shopperAcceptHeader'],
                'shopperEmailAddress' => $orderDetails['shopperEmailAddress']
            ];

            $this->_debug('Card Order Request ' . print_r($createOrderRequest, true));
            $response = $worldpay->createOrder($createOrderRequest);
            $this->_debug('Card Order Response ' . print_r($response, true));

            if (isset($response['amount'])) {
                $payment->setAdditionalInformation("amount", $response['amount']);
            }
            if (isset($response['currencyCode'])) {
                $payment->setAdditionalInformation("currencyCode", $response['currencyCode']);
            }
            if (isset($response['paymentResponse']['cardType'])) {
                $payment->setAdditionalInformation("cardType", $response['paymentResponse']['cardType']);
            }
            if (isset($response['paymentResponse']['maskedCardNumber'])) {
                $payment->setAdditionalInformation("maskedCardNumber", $response['paymentResponse']['maskedCardNumber']);
            }
            if (isset($response['paymentResponse']['expiryMonth'])) {
                $payment->setAdditionalInformation("expiryMonth", $response['paymentResponse']['expiryMonth']);
            }
            if (isset($response['paymentResponse']['expiryYear'])) {
                $payment->setAdditionalInformation("expiryYear", $response['paymentResponse']['expiryYear']);
            }
            if (isset($response['paymentStatus'])) {
                $payment->setAdditionalInformation("paymentStatus", $response['paymentStatus']);
            }
            if (isset($response['resultCodes']['avsResultCode'])) {
                $payment->setAdditionalInformation("avsResultCode", $response['resultCodes']['avsResultCode']);
            }
            if (isset($response['resultCodes']['cvcResultCode'])) {
                $payment->setAdditionalInformation("cvcResultCode", $response['resultCodes']['cvcResultCode']);
            }

            if ($response['paymentStatus'] === 'SUCCESS') {
                $this->_debug('Card Order: ' . $response['orderCode'] . ' SUCCESS');
                $payment->setAmount($amount);
                $payment->setIsTransactionClosed(false)
                    ->setShouldCloseParentTransaction(false)
                    ->setCcTransId($response['orderCode'])
                    ->setLastTransId($response['orderCode'])
                    ->setTransactionId($response['orderCode']);
                $payment->setAdditionalInformation("worldpayOrderCode", $response['orderCode']);
                if (!$response['is3DSOrder']) {
                    if ($payment->isCaptureFinal($amount)) {
                        $payment->setShouldCloseParentTransaction(true);
                    }
                } else {
                    return $response;
                }
            } elseif ($response['paymentStatus'] == 'AUTHORIZED') {
                $this->_debug('Card Order: ' . $response['orderCode'] . ' AUTHORIZED');
                $this->setStore($payment->getOrder()->getStoreId());
                $payment->setStatus(self::STATUS_APPROVED)
                    ->setCcTransId($response['orderCode'])
                    ->setLastTransId($response['orderCode'])
                    ->setTransactionId($response['orderCode'])
                    ->setIsTransactionClosed(false)
                    ->setAmount($amount)
                    ->setShouldCloseParentTransaction(false);
                $payment->setAdditionalInformation("worldpayOrderCode", $response['orderCode']);
                if (!$response['is3DSOrder']) {
                    if ($payment->isCaptureFinal($amount)) {
                        $payment->setShouldCloseParentTransaction(true);
                    }
                } else {
                    return $response;
                }
            } elseif ($response['paymentStatus'] == 'PRE_AUTHORIZED' && $response['is3DSOrder']) {
                $this->_debug('Card Order Request: ' . $response['orderCode'] . ' PRE_AUTHORIZED');
                $payment->setAmount($amount);
                $payment->setAdditionalInformation("worldpayOrderCode", $response['orderCode']);
                $payment->setLastTransId($orderId);
                $payment->setTransactionId($response['orderCode']);
                $payment->setIsTransactionClosed(false);
                $payment->setCcTransId($response['orderCode']);
                $payment->save();
                return $response;
            } else {
                if (isset($response['paymentStatusReason'])) {
                    throw new LocalizedException(__($response['paymentStatusReason']));
                } else {
                    throw new LocalizedException(__(print_r($response, true)));
                }
            }
        } catch (\Worldpay\WorldpayException $e) {
            $this->_debug($e->getMessage());
            throw new LocalizedException(__('Payment failed, please try again later ' . $e->getMessage()));
        }
    }

    public function capture(InfoInterface $payment, $amount)
    {
        $this->_debug('Card capture Called');
        $worldpayOrderCode = $payment->getData('last_trans_id');
        if ($worldpayOrderCode) {
            $worldpay = $this->setupWorldpay();
            try {
                $worldpay->captureAuthorizedOrder($worldpayOrderCode, $amount * 100);
                $payment->setAdditionalInformation("worldpayOrderCode", $worldpayOrderCode);
                $payment->setShouldCloseParentTransaction(1)->setIsTransactionClosed(1);
                $this->_debug('Capture Order: ' . $worldpayOrderCode . ' success');
            } catch (\Exception $e) {
                $this->_debug('Capture Order: ' . $worldpayOrderCode . ' failed with ' . $e->getMessage());
                throw new LocalizedException(__('Payment failed, please try again later ' . $e->getMessage()));
            }
        } elseif (!$payment->getAdditionalInformation("worldpayOrderCode")) {
            $payment->setAdditionalInformation('payment_type', 'capture');
            return $this->createOrder($payment, $amount, false);
        } else {
            if ($this->backendAuthSession->isLoggedIn()) {
                $payment->setAdditionalInformation('payment_type', 'capture');
                return $this->createOrder($payment, $amount, false);
            }
        }
        return $this;
    }

    public function isInitializeNeeded()
    {
        $threeDS = $this->config->threeDSEnabled();

        if ($threeDS && !$this->backendAuthSession->isLoggedIn()) {
            return true;
        } else {
            return false;
        }
    }

    public function createThreedsOrder($token, $quote)
    {
        $this->_debug('Card createThreedsOrder Called');
        $orderId = $quote->getReservedOrderId();
        $payment = $quote->getPayment();
        $amount = $quote->getGrandTotal();
        $currency_code = $quote->getQuoteCurrencyCode();
        $authorizeOnly = $this->config->isAuthorizeOnly();
        return $this->createWorldpayOrder($orderId, $payment, $token, $amount, $currency_code, $authorizeOnly, true, $quote);
    }

    public function updateOrder($status, $orderCode, $order, $payment, $amount)
    {
        parent::updateOrder($status, $orderCode, $order, $payment, $amount);
    }

    public function getGenerateOrder3DSUrl()
    {
        return $this->urlBuilder->getUrl('worldpay/threeds/create', ['_secure' => true]);
    }

    public function getGenerateOrderUrl()
    {
        return $this->urlBuilder->getUrl('worldpay/card/create', ['_secure' => true]);
    }

    public function authorise3DSOrder($paRes, $order)
    {
        $this->_debug('Card authorise3DSOrder Called');
        $wordpayOrderCode = $order->getPayment()->getAdditionalInformation("worldpayOrderCode");
        $worldpay = $this->setupWorldpay();

        if (!$wordpayOrderCode) {
            $this->_debug('No order id found in session!');
            throw new \Exception('Failed - There was a problem authorising your 3DS order');
        }

        $this->_debug('Authorising 3DS Order: ' . $wordpayOrderCode . ' with paRes: ' . $paRes);

        $response = $worldpay->authorize3DSOrder($wordpayOrderCode, $paRes);
        if (isset($response['paymentStatus']) && ($response['paymentStatus'] == 'SUCCESS' || $response['paymentStatus'] == 'AUTHORIZED')) {
            $this->_debug('Order: ' . $wordpayOrderCode . ' 3DS authorised successfully');
            return true;
        } else {
            $this->_debug('Order: ' . $wordpayOrderCode . ' 3DS failed authorising');
            throw new \Exception((isset($response['paymentStatus']) ? $response['paymentStatus'] : "FAILED") . ' - There was a problem authorising your 3DS order');
        }
    }

    public function cancel(InfoInterface $payment)
    {
        $worldpayOrderCode = $payment->getAdditionalInformation('worldpayOrderCode');
        $worldpay = $this->setupWorldpay();
        if ($worldpayOrderCode) {
            try {
                $worldpay->cancelAuthorizedOrder($worldpayOrderCode);
            } catch (\Exception $e) {
                throw new LocalizedException(__('Void failed, please try again later ' . $e->getMessage()));
            }
        }
        return true;
    }
}
