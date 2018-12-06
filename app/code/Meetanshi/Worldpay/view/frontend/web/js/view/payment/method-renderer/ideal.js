/*browser:true*/
/*global define*/
define(
     [
        'Meetanshi_Worldpay/js/view/payment/method-renderer/apm'
    ],
    function (Apm) {
        'use strict';
        return Apm.extend({
            defaults: {
                template: 'Meetanshi_Worldpay/form/ideal',
                paymentToken: false,
                shopperBankCode: ''
            },
            initObservable: function () {
                this._super()
                    .observe('paymentToken')
                    .observe('shopperBankCode');
                return this;
            },
            createIdealToken: function() {
                var fields = document.createElement("input");
                fields.setAttribute('type',"hidden");
                fields.setAttribute('id',"wp-shopperbank-code");
                fields.setAttribute('data-worldpay-apm', 'shopperBankCode');
                fields.setAttribute('value', this.shopperBankCode());
                this.createToken(false, false, fields);
            }
        });
    }
);
