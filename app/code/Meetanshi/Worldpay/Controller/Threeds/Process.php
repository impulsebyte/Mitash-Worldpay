<?php

namespace Meetanshi\Worldpay\Controller\Threeds;

class Process extends Threeds
{
    public function execute()
    {
        $post = $this->getRequest()->getParams();

        if (!isset($post['PaRes'])) {
            throw new \Exception('No PaRes found');
        }
        $paRes = $post['PaRes'];

        $incrementId = $this->checkoutSession->getLastRealOrderId();

        $order = $this->orderFactory->create()->loadByIncrementId($incrementId);

        try {
            $this->wordpayPaymentsCard->authorise3DSOrder($paRes, $order);
        } catch (\Exception $e) {
            $this->checkoutSession->restoreQuote();

            print_r("<script>");
            print_r("  parent.window.magento2.t.threeDSOn(false);");
            print_r("  parent.window.magento2.t.messageContainer.addErrorMessage({");
            print_r("      message: '{$e->getMessage()}'");
            print_r("  });");
            print_r("  parent.document.getElementById('wp_threeds_zone').innerHTML = '';");
            print_r("</script>");
        }

        $wordpayOrderCode = $order->getPayment()->getAdditionalInformation("worldpayOrderCode");
        $payment = $order->getPayment();


        $worldpayClass = $this->wordpayPaymentsCard->setupWorldpay();

        // Update order
        $wpOrder = $worldpayClass->getOrder($wordpayOrderCode);

        if ($wpOrder['paymentStatus'] == 'AUTHORIZED') {
            $wpOrder['amount'] = $wpOrder['authorizedAmount'];
        }
        $amount = $wpOrder['amount'] / 100;
        $this->wordpayPaymentsCard->updateOrder($wpOrder['paymentStatus'], $wpOrder['orderCode'], $order, $payment, $amount);

        //$this->orderSender->send($order);

        $quoteId = $order->getQuoteId();

        $this->checkoutSession->setLastQuoteId($quoteId)->setLastSuccessQuoteId($quoteId);

        print_r('<script>parent.location.href="' . $this->urlBuilder->getUrl('checkout/onepage/success', ['_secure' => true]) . '"</script>');
    }
}
