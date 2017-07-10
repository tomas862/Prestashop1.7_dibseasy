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

{extends file='page.tpl'}

{block name='page_content'}
<div class="row">
    <div class="col-md-8">
        <section class="checkout-step">
            <h1 class="step-title h3">
                {l s='Shipping Method' mod='dibs'}
            </h1>

            <br>

            <form class="clearfix"
                  id="js-delivery"
                  data-url-update="{url entity='order' params=['ajax' => 1, 'action' => 'selectDeliveryOption']}"
                  method="post"
            >
                <div class="form-fields">
                    <div class="delivery-options">
                        {foreach from=$delivery_options item=carrier key=carrier_id}
                            <div class="row delivery-option">
                                <div class="col-sm-1">
                                    <span class="custom-radio float-xs-left">
                                        <input type="radio" name="delivery_option[{$id_address}]" id="delivery_option_{$carrier.id}" value="{$carrier_id}"{if $delivery_option == $carrier_id} checked{/if}>
                                        <span></span>
                                    </span>
                                </div>
                                <label for="delivery_option_{$carrier.id}" class="col-sm-11 delivery-option-2">
                                    <div class="row">
                                        <div class="col-sm-5 col-xs-12">
                                            <div class="row">
                                                {if $carrier.logo}
                                                    <div class="col-xs-3">
                                                        <img src="{$carrier.logo}" alt="{$carrier.name}" />
                                                    </div>
                                                {/if}
                                                <div class="{if $carrier.logo}col-xs-9{else}col-xs-12{/if}">
                                                    <span class="h6 carrier-name">{$carrier.name}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4 col-xs-12">
                                            <span class="carrier-delay">{$carrier.delay}</span>
                                        </div>
                                        <div class="col-sm-3 col-xs-12">
                                            <span class="carrier-price">{$carrier.price}</span>
                                        </div>
                                    </div>
                                </label>
                            </div>
                            <div class="row carrier-extra-content"{if $delivery_option != $carrier_id} style="display:none;"{/if}>
                                {$carrier.extraContent nofilter}
                            </div>
                            <div class="clearfix"></div>
                            <br>
                        {/foreach}
                        <div id="delivery">
                            <label for="delivery_message">{l s='If you would like to add a comment about your order, please write it in the field below.' d='Shop.Theme.Checkout'}</label>
                            <textarea rows="2" cols="75" id="delivery_message" name="delivery_message">{$delivery_message}</textarea>
                        </div>
                    </div>

                </div>
            </form>
        </section>

        <hr>

        <div id="dibs-complete-checkout"></div>
    </div>
    <div class="col-md-4">

        {block name='cart_summary'}
            {include file='checkout/_partials/cart-summary.tpl' cart=$cart}
        {/block}

        {hook h='displayReassurance'}
    </div>
</div>
{/block}
