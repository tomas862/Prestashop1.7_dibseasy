<?php
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

namespace Invertus\DibsEasy\Action;

use Cart;
use Currency;
use DibsOrderPayment;
use Invertus\DibsEasy\Adapter\LinkAdapter;
use Invertus\DibsEasy\Payment\PaymentCreateRequest;
use Invertus\DibsEasy\Service\PaymentService;
use Module;

/**
 * Class PaymentCreateAction
 *
 * @package Invertus\DibsEasy\Action
 */
class PaymentCreateAction extends AbstractAction
{
    /**
     * @var PaymentService
     */
    private $paymentService;

    /**
     * @var LinkAdapter
     */
    private $linkAdapter;

    /**
     * @var Module
     */
    private $module;

    /**
     * @var array
     */
    private $supportedCountries;

    /**
     * PaymentCreateAction constructor.
     *
     * @param PaymentService $paymentService
     * @param LinkAdapter $linkAdapter
     * @param Module $module
     * @param array $supportedCountries
     */
    public function __construct(
        PaymentService $paymentService,
        LinkAdapter $linkAdapter,
        Module $module,
        array $supportedCountries
    ) {
        $this->paymentService = $paymentService;
        $this->linkAdapter = $linkAdapter;
        $this->module = $module;
        $this->supportedCountries = $supportedCountries;
    }

    /**
     * Create payment for given order
     *
     * @param Cart $cart
     *
     * @return DibsOrderPayment|false
     */
    public function createPayment(Cart $cart)
    {
        $currency = new Currency($cart->id_currency);

        $request = new PaymentCreateRequest();
        $request->setAmount($cart->getOrderTotal());
        $request->setCurrency($currency->iso_code);
        $request->setReference($cart->id);
        $request->setUrl($this->linkAdapter->getModuleLink('dibseasy', 'checkout'));
        $request->setShippingCountries($this->supportedCountries);

        $items = $this->getCartProductItems($cart);
        $request->setItems($items);

        $additionalItems = $this->getCartAdditionalItems($cart);
        foreach ($additionalItems as $item) {
            $request->addItem($item);
        }

        $paymentId = $this->paymentService->createPayment($request);
        if (!$paymentId) {
            return false;
        }

        $orderPayment = new DibsOrderPayment();
        $orderPayment->id_payment = $paymentId;
        $orderPayment->id_cart = $cart->id;
        $orderPayment->save();

        return $orderPayment;
    }

    /**
     * Module instance used for translations
     *
     * @return \DibsEasy
     */
    protected function getModule()
    {
        return $this->module;
    }
}
