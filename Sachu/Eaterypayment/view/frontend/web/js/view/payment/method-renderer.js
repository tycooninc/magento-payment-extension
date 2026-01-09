define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'eaterypayment',
                component: 'Sachu_Eaterypayment/js/view/payment/method-renderer/eaterypayment'
            }
        );
        return Component.extend({});
    }
);
