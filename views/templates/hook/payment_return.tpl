{**
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
*}

<div class="col-md-6">
    <ul>
        <li>{l s='Order status' mod='dibseasy'}: {$currentOrderState|escape:'htmlall':'UTF-8'}</li>
        <li>
            <a class=""
                   href="{$orderDetailsUrl|escape:'htmlall':'UTF-8'}"
                   title="{l s='Go to your order details page' mod='dibseasy'}"
            >
                <i class="icon-chevron-left"></i>{l s='View order details' mod='dibseasy'}
            </a>
        </li>
    </ul>
</div>