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
                template: 'Meetanshi_Worldpay/form/giropay',
                paymentToken: false,
                swiftCode: ''
            },
            initObservable: function () {
                this._super()
                    .observe('paymentToken')
                    .observe('swiftCode');
                return this;
            },
            createGiropayToken: function () {
                var fields = document.createElement("input");
                fields.setAttribute('type', "hidden");
                fields.setAttribute('id', "wp-swift-code");
                fields.setAttribute('data-worldpay-apm', 'swiftCode');
                fields.setAttribute('value', this.swiftCode());
                this.createToken(false, false, fields);
            }
        });
    }
);
