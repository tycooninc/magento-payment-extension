/*browser:true*/
/*global define*/
define(
    [
        'ko',
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Sachu_Eaterypayment/js/action/set-payment-method-action'
    ],
    function (ko, $, Component, setPaymentMethodAction) {
        'use strict';
        return Component.extend({
            defaults: {
                redirectAfterPlaceOrder: false,
                template: 'Sachu_Eaterypayment/payment/eaterypayment'
            },
            afterPlaceOrder: function () {
                setPaymentMethodAction(this.messageContainer);
                return false;
            },
            getTitle: function () {
                return this.item.title;
            }
        });
    }
);
