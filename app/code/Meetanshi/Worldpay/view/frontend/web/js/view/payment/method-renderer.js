define(
    [
        'jquery',
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ], function ($,
                 Component,
                 rendererList) {
        'use strict';

        window.WorldpayMagentoVersion = '1.0.0';
        var defaultComponent = 'Meetanshi_Worldpay/js/view/payment/method-renderer/card';
        var apmComponent = 'Meetanshi_Worldpay/js/view/payment/method-renderer/apm';
        var giropayComponent = 'Meetanshi_Worldpay/js/view/payment/method-renderer/giropay';
        var idealComponent = 'Meetanshi_Worldpay/js/view/payment/method-renderer/ideal';

        var methods = [
            {type: 'card', component: defaultComponent},
            {type: 'paypal4', component: apmComponent},
            {type: 'giropay', component: giropayComponent},
            {type: 'alipay', component: apmComponent},
            {type: 'mistercash', component: apmComponent},
            {type: 'przelewy24', component: apmComponent},
            {type: 'paysafecard', component: apmComponent},
            {type: 'postepay', component: apmComponent},
            {type: 'qiwi', component: apmComponent},
            {type: 'sofort', component: apmComponent},
            {type: 'yandex', component: apmComponent},
            {type: 'ideal', component: idealComponent}
        ];

        $.each(methods, function (k, method) {
            rendererList.push(method);
        });

        return Component.extend({});
    }
);