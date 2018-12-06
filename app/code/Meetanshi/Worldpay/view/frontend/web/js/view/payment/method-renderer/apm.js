/*browser:true*/
/*global define*/
define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'worldpay',
        'Magento_Checkout/js/action/set-payment-information',
        'Magento_Checkout/js/model/full-screen-loader',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/quote',
        'Magento_Checkout/js/model/full-screen-loader'
    ],
    function ($, Component, wp, setPaymentInformationAction, fullScreenLoadern, checkoutData, quote, fullScreenLoader) {
        'use strict';
        var wpConfig = window.checkoutConfig.payment.worldpay;
        return Component.extend({
            defaults: {
                template: 'Meetanshi_Worldpay/form/apm',
                paymentToken: false
            },
            initObservable: function () {
                this._super()
                    .observe('paymentToken');
                return this;
            },
            createToken: function (element, event, extraInput) {
                this.paymentToken(false);
                this.isPlaceOrderActionAllowed(false);
                var self = this;

                var form = document.createElement("form");
                var fields = document.createElement("input");
                fields.setAttribute('type', "hidden");
                fields.setAttribute('id', "wp-apm-name");
                fields.setAttribute('data-worldpay', 'apm-name');
                fields.setAttribute('value', this.getCode().replace('worldpay_payments_', ''));
                form.appendChild(fields);

                var fields = document.createElement("input");
                fields.setAttribute('type', "hidden");
                fields.setAttribute('id', "wp-country-code");
                fields.setAttribute('data-worldpay', 'country-code');
                fields.setAttribute('value', wpConfig.country_code);
                form.appendChild(fields);

                var fields = document.createElement("input");
                fields.setAttribute('type', "hidden");
                fields.setAttribute('id', "wp-country-code");
                fields.setAttribute('data-worldpay', 'language-code');
                fields.setAttribute('value', wpConfig.language_code);
                form.appendChild(fields);

                Worldpay.reusable = false;

                if (extraInput) {
                    form.appendChild(extraInput);
                }

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
                                fullScreenLoader.startLoader();
                                $.ajax({
                                    type: 'POST',
                                    url: wpConfig.redirect_url,
                                    success: function (response) {
                                        if (response.success) {
                                            $.mage.redirect(response.redirectURL);
                                        } else {
                                            self.messageContainer.addErrorMessage({
                                                message: response.error || "Error, please try again"
                                            });
                                            fullScreenLoader.stopLoader();
                                        }
                                    },
                                    error: function (response) {
                                        fullScreenLoader.stopLoader();
                                        self.messageContainer.addErrorMessage({
                                            message: "Error, please try again"
                                        });
                                    }
                                });
                            }).fail(function () {
                                self.isPlaceOrderActionAllowed(true);
                            });
                        }
                    } else {
                        self.isPlaceOrderActionAllowed(true);
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
            },
            getName: function () {
                return this.item.title;
            }
        });
    }
);
