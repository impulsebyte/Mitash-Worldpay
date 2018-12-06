/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/payment/additional-validators',
        'worldpay',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, additionalValidators, wp, setPaymentInformationAction, fullScreenLoader) {
        'use strict';
        var wpConfig = window.checkoutConfig.payment.worldpay;
        return Component.extend({
            defaults: {
                template: 'Meetanshi_Worldpay/form/paypal',
                paymentToken: false
            },
            initObservable: function () {
                this._super()
                    .observe('paymentToken');
                return this;
            },
            createPayPalToken: function () {
                this.paymentToken(false);
                this.isPlaceOrderActionAllowed(false);
                var self = this;
                // Create virtual form for WPJS
                var form = document.createElement("form");
                var fields = document.createElement("input");
                fields.setAttribute('type', "hidden");
                fields.setAttribute('id', "wp-apm-name");
                fields.setAttribute('data-worldpay', 'apm-name');
                fields.setAttribute('value', 'paypal4');
                form.appendChild(fields);

                var fields = document.createElement("input");
                fields.setAttribute('type', "hidden");
                fields.setAttribute('id', "wp-country-code");
                fields.setAttribute('data-worldpay', 'country-code');
                fields.setAttribute('value', 'GB');
                form.appendChild(fields);

                Worldpay.reusable = false;
                Worldpay.setClientKey(wpConfig.client_key);
                Worldpay.apm.createToken(form, function (resp, message) {

                    if (resp != 200) {
                        self.isPlaceOrderActionAllowed(true);
                        alert(message.error.message);
                        return;
                    }
                    if (message && message.token) {
                        if (!self.paymentToken()) {
                            self.paymentToken(message.token);

                            $.when(setPaymentInformationAction(self.messageContainer, {
                                'method': self.getCode(),
                                'additional_data': {
                                    "paymentToken": self.paymentToken()
                                }
                            })).done(function () {
                                $.mage.redirect(wpConfig.redirect_url);
                            }).fail(function () {
                                self.isPlaceOrderActionAllowed(true);
                            });
                        }
                    } else {
                        self.isPlaceOrderActionAllowed(true);
                        self.messageContainer.addErrorMessage({
                            message: "Error, please try again"
                        });
                    }
                });
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        "paymentToken": this.paymentToken()
                    }
                };

            }
        });
    }
);