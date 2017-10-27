/**
 * 2016 - 2017 Invertus, UAB
 *
 * NOTICE OF LICENSE
 *
 * This file is proprietary and can not be copied and/or distributed
 * without the express permission of INVERTUS, UAB
 *
 * @author    INVERTUS, UAB www.invertus.eu <support@invertus.eu>
 * @copyright Copyright (c) permanent, INVERTUS, UAB
 * @license   Addons PrestaShop license limitation
 *
 * International Registered Trademark & Property of INVERTUS, UAB
 */

$(document).ready(function() {
    replaceCheckoutUrl();

    prestashop.on('updateCart', function () {
        setTimeout(replaceCheckoutUrl, 1000);
    });

    function replaceCheckoutUrl()
    {
        var $checkoutButton = $('.checkout.cart-detailed-actions');
        if (0 < $checkoutButton.length) {
            $checkoutButton.find('.btn.btn-primary').attr('href', dibsGlobal.checkoutUrl);
        }
    }
});
