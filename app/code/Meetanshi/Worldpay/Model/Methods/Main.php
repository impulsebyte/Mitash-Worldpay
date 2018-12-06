<?php

namespace Meetanshi\Worldpay\Model\Methods;

use Magento\Framework\Api\AttributeValueFactory;
use Magento\Framework\Api\ExtensionAttributesFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Payment\Model\InfoInterface;
use Magento\Framework\Exception\LocalizedException;
use Meetanshi\Worldpay\Model\Config;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Payment\Helper\Data;
use Magento\Payment\Model\Method\Logger as PaymentLogger;
use Magento\Backend\Model\Auth\Session;
use Magento\Checkout\Model\Cart;
use Magento\Framework\UrlInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\DB\TransactionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Meetanshi\Worldpay\Model\ResourceModel\Cards\CollectionFactory;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Checkout\Helper\Data as CheckoutData;
use Magento\Quote\Api\CartRepositoryInterface;
use Magento\Quote\Api\CartManagementInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Backend\Model\Session\Quote;
use Meetanshi\Worldpay\Logger\Logger;
use Magento\Sales\Model\Order;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Data\Collection\AbstractDb;

class Main extends AbstractMethod
{
    protected $_isInitializeNeeded = true;
    protected $_isGateway = true;
    protected $_canAuthorize = true;
    protected $_canCapture = true;
    protected $_canCapturePartial = true;
    protected $_canVoid = true;
    protected $_canUseInternal = true;
    protected $_canUseCheckout = true;
    protected $_canRefund = true;
    protected $_canRefundInvoicePartial = true;
    protected $backendAuthSession;
    protected $cart;
    protected $urlBuilder;
    protected $_objectManager;
    protected $invoiceSender;
    protected $transactionFactory;
    protected $customerSession;
    protected $savedCardFactory;
    protected $checkoutSession;
    protected $checkoutData;
    protected $quoteRepository;
    protected $quoteManagement;
    protected $orderSender;
    protected $sessionQuote;
    protected $serialize;

    public function __construct(
        Context $context,
        Registry $registry,
        ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $customAttributeFactory,
        Data $paymentData,
        ScopeConfigInterface $scopeConfig,
        PaymentLogger $logger,
        Session $backendAuthSession,
        Config $config,
        Cart $cart,
        UrlInterface $urlBuilder,
        ObjectManagerInterface $objectManager,
        InvoiceSender $invoiceSender,
        TransactionFactory $transactionFactory,
        CustomerSession $customerSession,
        CollectionFactory $savedCardFactory,
        CheckoutSession $checkoutSession,
        CheckoutData $checkoutData,
        CartRepositoryInterface $quoteRepository,
        CartManagementInterface $quoteManagement,
        OrderSender $orderSender,
        Quote $sessionQuote,
        Logger $wpLogger,
        Order $order,
        Json $serialize,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $extensionFactory,
            $customAttributeFactory,
            $paymentData,
            $scopeConfig,
            $logger,
            $resource,
            $resourceCollection,
            $data
        );
        $this->urlBuilder = $urlBuilder;
        $this->backendAuthSession = $backendAuthSession;
        $this->config = $config;
        $this->cart = $cart;
        $this->_objectManager = $objectManager;
        $this->invoiceSender = $invoiceSender;
        $this->transactionFactory = $transactionFactory;
        $this->customerSession = $customerSession;
        $this->savedCardFactory = $savedCardFactory;
        $this->checkoutSession = $checkoutSession;
        $this->checkoutData = $checkoutData;
        $this->quoteRepository = $quoteRepository;
        $this->quoteManagement = $quoteManagement;
        $this->orderSender = $orderSender;
        $this->sessionQuote = $sessionQuote;
        $this->logger = $wpLogger;
        $this->order = $order;
        $this->serialize = $serialize;
    }

    public function initialize($paymentAction, $stateObject)
    {
        $this->_debug('Worldpay initialize Called');
        $stateObject->setState(\Magento\Sales\Model\Order::STATE_PENDING_PAYMENT);
        $stateObject->setStatus('pending_payment');
        $stateObject->setIsNotified(false);
    }

    protected function _debug($debugData)
    {
        if ($this->config->debugMode($this->_code)) {
            $this->logger->debug($debugData);
        }
    }

    public function getOrderPlaceRedirectUrl()
    {
        return $this->urlBuilder->getUrl('worldpay/main/redirect', ['_secure' => true]);
    }

    public function assignData(\Magento\Framework\DataObject $data)
    {
        $this->_debug('Worldpay assignData Called');
        parent::assignData($data);

        $_tmpData = $data->_data;
        $_serializedAdditionalData = $this->serialize->serialize($_tmpData['additional_data']);
        $additionalDataRef = $_serializedAdditionalData;
        $additionalDataRef = $this->serialize->unserialize($additionalDataRef);
        $_paymentToken = $additionalDataRef['paymentToken'];

        $infoInstance = $this->getInfoInstance();
        $infoInstance->setAdditionalInformation('payment_token', $_paymentToken);
        return $this;
    }

    /**
     * @param \Magento\Quote\Model\Quote $quote
     * @return bool
     * @throws \Exception
     */
    public function createApmOrder($quote)
    {
        $this->_debug('Worldpay createApmOrder Called');
        $orderId = $quote->getReservedOrderId();
        $payment = $quote->getPayment();
        $token = $payment->getAdditionalInformation('payment_token');
        $amount = $quote->getGrandTotal();

        $worldpay = $this->setupWorldpay();

        $currency_code = $quote->getQuoteCurrencyCode();

        $orderDetails = $this->getSharedOrderDetails($quote, $currency_code);

        try {
            $createOrderRequest = [
                'token' => $token,
                'orderDescription' => $orderDetails['orderDescription'],
                'amount' => $amount * 100,
                'currencyCode' => $orderDetails['currencyCode'],
                'siteCode' => $orderDetails['siteCode'],
                'name' => $orderDetails['name'],
                'billingAddress' => $orderDetails['billingAddress'],
                'deliveryAddress' => $orderDetails['deliveryAddress'],
                'customerOrderCode' => $orderId,
                'settlementCurrency' => $orderDetails['settlementCurrency'],
                'successUrl' => $this->urlBuilder->getUrl('worldpay/main/success', ['_secure' => true]),
                'pendingUrl' => $this->urlBuilder->getUrl('worldpay/main/pending', ['_secure' => true]),
                'failureUrl' => $this->urlBuilder->getUrl('worldpay/main/failure', ['_secure' => true]),
                'cancelUrl' => $this->urlBuilder->getUrl('worldpay/main/cancel', ['_secure' => true]),
                'shopperIpAddress' => $orderDetails['shopperIpAddress'],
                'shopperSessionId' => $orderDetails['shopperSessionId'],
                'shopperUserAgent' => $orderDetails['shopperUserAgent'],
                'shopperAcceptHeader' => $orderDetails['shopperAcceptHeader'],
                'shopperEmailAddress' => $orderDetails['shopperEmailAddress']
            ];
            $this->_debug('Worldpay Order Request: ' . print_r($createOrderRequest, true));
            $response = $worldpay->createApmOrder($createOrderRequest);
            $this->_debug('Worldpay Order Response: ' . print_r($response, true));

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
                $this->_debug('Worldpay Order Request: ' . $response['orderCode'] . ' SUCCESS');
                $payment->setIsTransactionClosed(false)
                    ->setTransactionId($response['orderCode'])
                    ->setShouldCloseParentTransaction(false);
                if ($payment->isCaptureFinal($amount)) {
                    $payment->setShouldCloseParentTransaction(true);
                }
            } elseif ($response['paymentStatus'] == 'PRE_AUTHORIZED') {
                $this->_debug('Worldpay Order Request: ' . $response['orderCode'] . ' PRE_AUTHORIZED');
                $payment->setAmount($amount);
                $payment->setAdditionalInformation("worldpayOrderCode", $response['orderCode']);
                $payment->setLastTransId($orderId);
                $payment->setTransactionId($response['orderCode']);
                $payment->setIsTransactionClosed(false);
                $payment->setCcTransId($response['orderCode']);
                $payment->save();
                return $response['redirectURL'];
            } else {
                if (isset($response['paymentStatusReason'])) {
                    throw new \Exception($response['paymentStatusReason']);
                } else {
                    throw new \Exception(print_r($response, true));
                }
            }
        } catch (\Exception $e) {
            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->_debug($e->getMessage());
            throw new \Exception('Payment failed, please try again later ' . $e->getMessage());
        }
        return false;
    }

    public function setupWorldpay()
    {
        $this->_debug('Worldpay setupWorldpay Called');
        $service_key = $this->config->getServiceKey();
        $worldpay = new \Worldpay\Worldpay($service_key);

        $worldpay->setPluginData('Magento2', '2.0.25');
        \Worldpay\Utils::setThreeDSShopperObject([
            'shopperIpAddress' => \Worldpay\Utils::getClientIp(),
            'shopperSessionId' => $this->customerSession->getSessionId(),
            'shopperUserAgent' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'shopperAcceptHeader' => '*/*'
        ]);
        return $worldpay;
    }

    protected function getSharedOrderDetails($quote, $currencyCode)
    {
        $this->_debug('Worldpay getSharedOrderDetails Called');
        $billing = $quote->getBillingAddress();
        $shipping = $quote->getShippingAddress();

        $data = [];

        $data['orderDescription'] = $this->config->getPaymentDescription();

        if (!$data['orderDescription']) {
            $data['orderDescription'] = "Magento 2 Order";
        }

        $data['currencyCode'] = $currencyCode;
        $data['name'] = $billing->getName();

        $data['billingAddress'] = [
            "address1" => $billing->getStreetLine(1),
            "address2" => $billing->getStreetLine(2),
            "address3" => $billing->getStreetLine(3),
            "postalCode" => $billing->getPostcode(),
            "city" => $billing->getCity(),
            "state" => "",
            "countryCode" => $billing->getCountryId(),
            "telephoneNumber" => $billing->getTelephone()
        ];

        if ($shipping) {
            $data['deliveryAddress'] = [
                "firstName" => $shipping->getFirstname(),
                "lastName" => $shipping->getLastname(),
                "address1" => $shipping->getStreetLine(1),
                "address2" => $shipping->getStreetLine(2),
                "address3" => $shipping->getStreetLine(3),
                "postalCode" => $shipping->getPostcode(),
                "city" => $shipping->getCity(),
                "state" => "",
                "countryCode" => $shipping->getCountryId(),
                "telephoneNumber" => $shipping->getTelephone()
            ];
        } else {
            $data['deliveryAddress'] = [];
        }


        $data['shopperIpAddress'] = \Worldpay\Utils::getClientIp();
        $data['shopperSessionId'] = $this->customerSession->getSessionId();
        $data['shopperUserAgent'] = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $data['shopperAcceptHeader'] = '*/*';

        if ($this->backendAuthSession->isLoggedIn()) {
            $objectManager = \Magento\Framework\App\ObjectManager::getInstance();
            $customer = $objectManager->create('Magento\Customer\Model\Customer')->load($this->sessionQuote->getCustomerId());
            $data['shopperEmailAddress'] = $customer->getEmail();
        } else {
            $data['shopperEmailAddress'] = $this->customerSession->getCustomer()->getEmail();
        }
        $data['siteCode'] = null;
        $siteCodes = $this->config->getSiteCodes();
        if ($siteCodes) {
            foreach ($siteCodes as $siteCode) {
                $data['siteCode'] = $siteCode['site_code'];
                $data['settlementCurrency'] = $siteCode['settlement_currency'];
                break;
            }
        }
        if (!isset($data['settlementCurrency'])) {
            $data['settlementCurrency'] = $this->config->getSettlementCurrency();
        }
        return $data;
    }

    public function isTokenAllowed()
    {
        $this->_debug('Worldpay isTokenAllowed Called');
        return true;
    }

    public function capture(InfoInterface $payment, $amount)
    {
        $this->_debug('Worldpay capture Called');
        return $this;
    }

    public function authorize(InfoInterface $payment, $amount)
    {
        return $this;
    }

    public function refund(InfoInterface $payment, $amount)
    {
        if ($order = $payment->getOrder()) {
            $worldpay = $this->setupWorldpay();
            try {
                $grandTotal = $order->getGrandTotal();
                $a = $payment->getAdditionalInformation("worldpayOrderCode");
                if ($grandTotal == $amount) {
                    $worldpay->refundOrder($payment->getAdditionalInformation("worldpayOrderCode"));
                } else {
                    $worldpay->refundOrder($payment->getAdditionalInformation("worldpayOrderCode"), $amount * 100);
                }
                return $this;
            } catch (\Exception $e) {
                $a = $e->getMessage();
                throw new LocalizedException(__('Refund failed ' . $e->getMessage()));
            }
        }
    }

    public function void(InfoInterface $payment)
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

    public function cancel(InfoInterface $payment)
    {
        throw new LocalizedException(__('You cannot cancel an APM order'));
    }

    public function updateOrder($status, $orderCode, $order, $payment, $amount)
    {
        $this->_debug('Worldpay updateOrder Called');
        if ($status === 'REFUNDED' || $status === 'SENT_FOR_REFUND') {
            $payment
                ->setTransactionId($orderCode)
                ->setParentTransactionId($orderCode)
                ->setIsTransactionClosed(true)
                ->registerRefundNotification($amount);

            $this->_debug('Order: ' . $orderCode . ' REFUNDED');
        } elseif ($status === 'FAILED') {
            $order->cancel()->setState(\Magento\Sales\Model\Order::STATE_CANCELED, true, 'Gateway has declined the payment.')->save();
            $payment->setStatus(self::STATUS_DECLINED);

            $this->_debug('Order: ' . $orderCode . ' FAILED');
        } elseif ($status === 'SETTLED') {
            $this->_debug('Order: ' . $orderCode . ' SETTLED');
        } elseif ($status === 'AUTHORIZED') {
            $payment
                ->setTransactionId($orderCode)
                ->setShouldCloseParentTransaction(1)
                ->setIsTransactionClosed(0)
                ->registerAuthorizationNotification($amount, true);
            $this->_debug('Order: ' . $orderCode . ' AUTHORIZED');
        } elseif ($status === 'SUCCESS') {
            if ($order->canInvoice()) {
                $payment
                    ->setTransactionId($orderCode)
                    ->setShouldCloseParentTransaction(1)
                    ->setIsTransactionClosed(0);

                $invoice = $order->prepareInvoice();
                $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                $invoice->register();

                $transaction = $this->transactionFactory->create();

                $transaction->addObject($invoice)
                    ->addObject($invoice->getOrder())
                    ->save();

                //$this->invoiceSender->send($invoice);
                $order->addStatusHistoryComment(
                    __('Notified customer about invoice #%1.', $invoice->getId())
                )
                    ->setIsCustomerNotified(true);
            }
            $this->_debug('Order: ' . $orderCode . ' SUCCESS');
        } else {
            // Unknown status
            $order->addStatusHistoryComment('Unknown Worldpay Payment Status: ' . $status . ' for ' . $orderCode)
                ->setIsCustomerNotified(true);
        }
        $order->save();
    }

    public function readyMagentoQuote()
    {
        $this->_debug('Worldpay readyMagentoQuote Called');
        $quote = $this->checkoutSession->getQuote();

        $quote->reserveOrderId();
        $this->quoteRepository->save($quote);
        if ($this->getCheckoutMethod($quote) == \Magento\Checkout\Model\Type\Onepage::METHOD_GUEST) {
            $quote->setCustomerId(null)
                ->setCustomerEmail($quote->getBillingAddress()->getEmail())
                ->setCustomerIsGuest(true)
                ->setCustomerGroupId(\Magento\Customer\Model\Group::NOT_LOGGED_IN_ID);
        }

        $quote->getBillingAddress()->setShouldIgnoreValidation(true);
        if (!$quote->getIsVirtual()) {
            $quote->getShippingAddress()->setShouldIgnoreValidation(true);
            if (!$quote->getBillingAddress()->getEmail()
            ) {
                $quote->getBillingAddress()->setSameAsBilling(1);
            }
        }

        $quote->collectTotals();

        return $quote;
    }

    private function getCheckoutMethod($quote)
    {
        $this->_debug('Worldpay getCheckoutMethod Called');
        if ($this->customerSession->isLoggedIn()) {
            return \Magento\Checkout\Model\Type\Onepage::METHOD_CUSTOMER;
        }
        if (!$quote->getCheckoutMethod()) {
            if ($this->checkoutData->isAllowedGuestCheckout($quote)) {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_GUEST);
            } else {
                $quote->setCheckoutMethod(\Magento\Checkout\Model\Type\Onepage::METHOD_REGISTER);
            }
        }
        return $quote->getCheckoutMethod();
    }

    public function createMagentoOrder($quote)
    {
        $this->_debug('Worldpay createMagentoOrder Called');
        try {
            $order = $this->quoteManagement->submit($quote);
            return $order;
        } catch (\Exception $e) {
            $orderId = $quote->getReservedOrderId();
            $payment = $quote->getPayment();
            $token = $payment->getAdditionalInformation('payment_token');
            $amount = $quote->getGrandTotal();
            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->_debug($e->getMessage());

            $this->checkoutSession->restoreQuote();

            throw new \Exception($e->getMessage());
        }
    }

    public function sendMagentoOrder($order)
    {
        $this->_debug('Worldpay sendMagentoOrder Called');
        $this->checkoutSession->start();

        $this->checkoutSession->clearHelperData();

        $this->checkoutSession->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId())
            ->setLastOrderStatus($order->getStatus());
    }
}
