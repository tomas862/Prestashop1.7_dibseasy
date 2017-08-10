<?php
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

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

class DibsCheckoutModuleFrontController extends ModuleFrontController
{
    /**
     * @var Dibs
     */
    public $module;

    /**
     * @var bool
     */
    public $ssl = true;

    /**
     * @var array These variables are passed to JS
     */
    protected $jsVariables = [];

    /**
     * Check if customer can access checkout page.
     *
     * @retun bool
     */
    public function checkAccess()
    {
        // If guest checkout is enabled and customer is not logged in, then redirect to standard checkout
        $guestCheckoutEnabled = (bool) Configuration::get('PS_GUEST_CHECKOUT_ENABLED');
        if (!$guestCheckoutEnabled && !$this->context->customer->isLogged()) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // General checks
        if (!$this->module->active ||
            !$this->module->isConfigured()
        ) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        // If cart is not initialized or cart is empty redirect to default cart page
        if (!isset($this->context->cart) || $this->context->cart->nbProducts() <= 0) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        $currency = new Currency($this->context->cart->id_currency);
        $supportedCurrencies = $this->module->getParameter('supported_currencies');

        // If currency is not supported then redirect to default checkout
        if (!in_array($currency->iso_code, $supportedCurrencies)) {
            Tools::redirect('index.php?controller=order&step=1');
        }

        return true;
    }

    /**
     * Add custom JS & CSS to controller
     */
    public function setMedia()
    {
        parent::setMedia();

        $isTestingModeOn = (bool) Configuration::get('DIBS_TEST_MODE');
        switch ($isTestingModeOn) {
            case false:
                $checkoutJs = $this->module->getParameter('js_checkout_prod_url');
                $checkoutKey = Configuration::get('DIBS_PROD_CHECKOUT_KEY');
                break;
            default:
            case true:
                $checkoutJs = $this->module->getParameter('js_checkout_test_url');
                $checkoutKey = Configuration::get('DIBS_TEST_CHECKOUT_KEY');
                break;
        }

        $language = Configuration::get('DIBS_LANGUAGE');

        $changeDeliveryOptionUrl = $this->context->link->getModuleLink($this->module->name, 'checkout');
        $validationUrl = $this->context->link->getModuleLink($this->module->name, 'validation');

        $this->jsVariables['dibsCheckout']['checkoutKey'] = $checkoutKey;
        $this->jsVariables['dibsCheckout']['language'] = $language;
        $this->jsVariables['dibsCheckout']['validationUrl'] = $validationUrl;
        $this->jsVariables['dibsCheckout']['checkoutUrl'] = $changeDeliveryOptionUrl;

        $this->registerStylesheet('dibs-checkout-css', 'modules/dibs/views/css/checkout.css');
        $this->registerJavascript('dibs-remote-js', $checkoutJs, ['server' => 'remote']);
        $this->registerJavascript('dibs-checkout-js', 'modules/dibs/views/js/checkout.js');
    }

    /**
     * Process actions
     */
    public function postProcess()
    {
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);

        /** @var \Invertus\Dibs\Repository\OrderPaymentRepository $orderPaymentRepository */
        $orderPaymentRepository = $this->module->get('dibs.repository.order_payment');
        $orderPayment = $orderPaymentRepository->findOrderPaymentByCartId($this->context->cart->id);
        if ($orderPayment) {
            $orderPayment->delete();
        }

        if (Tools::isSubmit('paymentId')) {
            $paymentId = Tools::getValue('paymentId');

            /** @var \Invertus\Dibs\Action\PaymentGetAction $paymentGetAction */
            $paymentGetAction = $this->module->get('dibs.action.payment_get');
            $payment = $paymentGetAction->getPayment($paymentId);

            $paymentAmountInCents = $payment->getOrderDetail()->getAmount();
            $cartAmountInCents = (int) (string) ($this->context->cart->getOrderTotal() * 100);

            $paymentCurrency = $payment->getOrderDetail()->getCurrency();
            $cartCurrency = new Currency($this->context->cart->id_currency);

            if ($paymentAmountInCents == $cartAmountInCents && $cartCurrency->iso_code == $paymentCurrency) {
                $this->context->cookie->dibs_payment_id = $paymentId;
                Tools::redirect($this->context->link->getModuleLink($this->module->name, 'checkout'));
            }
        }

        if (isset($this->context->cookie->dibs_payment_id)) {
            $paymentId = $this->context->cookie->dibs_payment_id;
            unset($this->context->cookie->dibs_payment_id);

            $orderPayment = new DibsOrderPayment();
            $orderPayment->id_payment = $paymentId;
            $orderPayment->id_cart = $this->context->cart->id;
            $orderPayment->save();
        } else {
            /** @var \Invertus\Dibs\Action\PaymentCreateAction $paymentCreateAction */
            $paymentCreateAction = $this->module->get('dibs.action.payment_create');
            $orderPayment = $paymentCreateAction->createPayment($this->context->cart);

            $paymentId = $orderPayment->id_payment;
        }

        $this->jsVariables['dibsCheckout']['paymentID'] = $paymentId;
    }

    /**
     * Initialize header
     */
    public function initHeader()
    {
        parent::initHeader();

        Media::addJsDef($this->jsVariables);
    }

    /**
     * Initialize checkout content
     */
    public function initContent()
    {
        $idLang = $this->context->language->id;

        $this->assignDeliveryOptionVars();

        $this->context->smarty->assign([
            'regularCheckoutUrl' => $this->context->link->getPageLink('order', true, $idLang, ['step' => 1]),
            'cart' => $this->context->cart,
        ]);

        parent::initContent();

        $this->setTemplate('module:dibs/views/templates/front/checkout.tpl');
    }

    /**
     * Variables related to delivery options
     */
    protected function assignDeliveryOptionVars()
    {
        $deliveryOptionsFinder = new DeliveryOptionsFinder(
            $this->context,
            $this->getTranslator(),
            $this->objectPresenter,
            new PriceFormatter()
        );

        $message = '';
        if ($result = Message::getMessageByCartId($this->context->cart->id)) {
            $message = $result['message'];
        }

        $this->context->smarty->assign([
            'delivery_options' => $deliveryOptionsFinder->getDeliveryOptions(),
            'delivery_option' => $deliveryOptionsFinder->getSelectedDeliveryOption(),
            'delivery_message' => $message,
            'id_address' => $this->context->cart->id_address_delivery,
        ]);
    }
}
