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

class AdminDibsConfigurationController extends ModuleAdminController
{
    /****
     * @var Dibs
     */
    public $module;

    public $bootstrap = true;

    public function init()
    {
        $this->initOptions();

        $isFriendlyUrlOn = (bool) Configuration::get('PS_REWRITING_SETTINGS');
        if (!$isFriendlyUrlOn) {
            $this->warnings[] = $this->l('Friendly URLs must be enabled in order for module to work.');
        }

        parent::init();
    }

    /**
     * Initialize options
     */
    protected function initOptions()
    {
        $this->fields_options = [
            'dibs_configuration' => [
                'title' => $this->l('DIBS Easy Checkout configuration'),
                'fields' => [
                    'DIBS_MERCHANT_ID' => [
                        'title' => $this->l('Merchant ID'),
                        'type' => 'text',
                        'class' => 'fixed-width-xxl',
                        'size' => '30',
                    ],
                    'DIBS_PROD_SECRET_KEY' => [
                        'title' => $this->l('Live secret key'),
                        'type' => 'text',
                        'validation' => 'isString',
                        'class' => 'fixed-width-xxl',
                        'size' => '30',
                    ],
                    'DIBS_TEST_SECRET_KEY' => [
                        'title' => $this->l('Test secret key'),
                        'type' => 'text',
                        'validation' => 'isString',
                        'class' => 'fixed-width-xxl',
                        'size' => '30',
                    ],
                    'DIBS_PROD_CHECKOUT_KEY' => [
                        'title' => $this->l('Live checkout key'),
                        'type' => 'text',
                        'validation' => 'isString',
                        'class' => 'fixed-width-xxl',
                        'size' => '30',
                    ],
                    'DIBS_TEST_CHECKOUT_KEY' => [
                        'title' => $this->l('Test checkout key'),
                        'type' => 'text',
                        'validation' => 'isString',
                        'class' => 'fixed-width-xxl',
                        'size' => '30',
                    ],
                    'DIBS_LANGUAGE' => [
                        'title' => $this->l('Checkout language'),
                        'type' => 'select',
                        'class' => 'fixed-width-xxl',
                        'list' => $this->getLangs(),
                        'identifier' => 'id',
                    ],
                    'DIBS_TEST_MODE' => [
                        'title' => $this->l('Testing mode'),
                        'validation' => 'isBool',
                        'type' => 'bool',
                        'cast' => 'intval',
                        'class' => 'fixed-width-xxl',
                    ],
                ],
                'submit' => [
                    'title' => $this->l('Save'),
                ],
            ],
        ];
    }

    /**
     * Get languages in which DIBS are available
     *
     * @return array
     */
    private function getLangs()
    {
        return [
            [
                'id' => 'en-GB',
                'name' => $this->l('English'),
            ],
            [
                'id' => 'sv-SE',
                'name' => $this->l('Swedish'),
            ],
            [
                'id' => 'nb-NO',
                'name' => $this->l('Norwegian Bokmål'),
            ],
            [
                'id' => 'da-DK',
                'name' => $this->l('Danish'),
            ],
        ];
    }
}
