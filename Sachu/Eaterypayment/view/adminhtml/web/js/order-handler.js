define([
    'jquery',
    'domReady!'
], function ($) {
    'use strict';

    return function (config) {
        if(config.orderStatus === "pending"){
            document.getElementById("order_ship").style.display = "none";
            document.getElementById("sales_order_view_tabs_order_creditmemos").style.display = "none";
        }
    };
});
