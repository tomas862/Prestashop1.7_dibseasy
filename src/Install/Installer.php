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

namespace Invertus\DibsEasy\Install;

use Address;
use Country;
use Db;
use Exception;
use Invertus\DibsEasy\Adapter\ConfigurationAdapter;
use Invertus\DibsEasy\Adapter\LanguageAdapter;
use Invertus\DibsEasy\Adapter\ToolsAdapter;
use Module;
use OrderState;

/**
 * Class Installer
 *
 * @package Invertus\DibsEasy\Install
 */
class Installer
{
    /**
     * @var Module
     */
    private $module;

    /**
     * @var array
     */
    private $moduleConfiguration;

    /**
     * @var ConfigurationAdapter
     */
    private $configurationAdapter;

    /**
     * @var LanguageAdapter
     */
    private $languageAdapter;

    /**
     * @var Db
     */
    private $db;

    /**
     * @var ToolsAdapter
     */
    private $toolsAdapter;

    /**
     * Installer constructor.
     *
     * @param Module $module
     * @param ConfigurationAdapter $configurationAdapter
     * @param LanguageAdapter $languageAdapter
     * @param ToolsAdapter $toolsAdapter
     * @param Db $db
     * @param array $config
     */
    public function __construct(
        Module $module,
        ConfigurationAdapter $configurationAdapter,
        LanguageAdapter $languageAdapter,
        ToolsAdapter $toolsAdapter,
        Db $db,
        array $config
    ) {
        $this->module = $module;
        $this->moduleConfiguration = $config;
        $this->configurationAdapter = $configurationAdapter;
        $this->languageAdapter = $languageAdapter;
        $this->db = $db;
        $this->toolsAdapter = $toolsAdapter;
    }

    /**
     * Install module
     *
     * @return bool
     */
    public function install()
    {
        $this->registerHooks();
        $this->installConfiguration();
        $this->installOrderStates();
        $this->installDatabase();
        $this->installDefaultAddresses();

        return true;
    }

    /**
     * Uninstall module
     *
     * @return bool
     */
    public function uninstall()
    {
        $this->uninstallOrderStates();
        $this->uninstallDefaultAddresses();
        $this->uninstallConfiguration();
        $this->uninstallDatabase();

        return true;
    }

    /**
     * @return array
     */
    public function getTabs()
    {
        return $this->moduleConfiguration['tabs'];
    }

    /**
     * Register module hooks
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function registerHooks()
    {
        $hooks = $this->moduleConfiguration['hooks'];

        if (!$this->module->registerHook($hooks)) {
            throw new Exception('Failed to install hooks.');
        }

        return true;
    }

    /**
     * Install module default configuration
     *
     * @retrun bool
     *
     * @throws Exception
     */
    protected function installConfiguration()
    {
        $configuration = $this->moduleConfiguration['configuration'];

        foreach ($configuration as $name => $value) {
            // skip order state & address configuration
            // since those will be saved later
            if (false !== strpos($name, 'ORDER_STATE') || false !== strpos($name, 'ADDRESS_ID')) {
                continue;
            }

            if (!$this->configurationAdapter->set($name, $value)) {
                throw new Exception(sprintf('Failed to save "%s" configuration value.', $name));
            }
        }

        return true;
    }

    /**
     * Uninstall module configuration
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function uninstallConfiguration()
    {
        $configurationNames = array_keys($this->moduleConfiguration['configuration']);

        foreach ($configurationNames as $name) {
            if (!$this->configurationAdapter->remove($name)) {
                throw new Exception(sprintf('Failed to delete "%s" configuration value.', $name));
            }
        }

        return true;
    }

    /**
     * Install order states
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function installOrderStates()
    {
        $orderStates = $this->moduleConfiguration['order_states'];
        $idLangs = $this->languageAdapter->getIDs();

        foreach ($orderStates as $state) {
            $orderState = new OrderState();
            $orderState->color = $state['color'];
            $orderState->paid = $state['paid'];
            $orderState->invoice = $state['invoice'];
            $orderState->module_name = $this->module->name;
            $orderState->unremovable = 0;

            foreach ($idLangs as $idLang) {
                $orderState->name[$idLang] = $state['name'];
            }

            if (!$orderState->save()) {
                throw new Exception(sprintf('Failed to install "%s" order status.', $state['name']));
            }

            $this->configurationAdapter->set($state['config'], $orderState->id);
        }

        return true;
    }

    /**
     * Uninstall order states
     *
     * @return bool
     *
     * @throws Exception
     */
    protected function uninstallOrderStates()
    {
        $orderStates = $this->moduleConfiguration['order_states'];

        foreach ($orderStates as $state) {
            $idOrderState = $this->configurationAdapter->get($state['config']);

            $orderState = new OrderState($idOrderState);
            $orderState->deleted = 1;

            if (!$orderState->save()) {
                throw new Exception(sprintf('Failed to uninstall "%s" order status.', $state['name']));
            }
        }

        return true;
    }

    /**
     * Install database tables
     *
     * @retrun bool
     */
    protected function installDatabase()
    {
        $sqlStatements = $this->getSqlStatements($this->module->getLocalPath().'etc/sql/install.sql');

        return $this->db->execute($sqlStatements);
    }

    /**
     * Uninstall database
     *
     * @return bool
     */
    protected function uninstallDatabase()
    {
        $sqlStatements = $this->getSqlStatements($this->module->getLocalPath().'etc/sql/uninstall.sql');

        return $this->db->execute($sqlStatements);
    }

    /**
     * Format file sql statements
     *
     * @param string $fileName
     *
     * @return string
     */
    protected function getSqlStatements($fileName)
    {
        $sqlStatements = $this->toolsAdapter->fileGetContents($fileName);
        $sqlStatements = str_replace('PREFIX_', _DB_PREFIX_, $sqlStatements);
        $sqlStatements = str_replace('ENGINE_TYPE', _MYSQL_ENGINE_, $sqlStatements);

        return $sqlStatements;
    }

    /**
     * Install default delivery addresses for supported countries
     */
    protected function installDefaultAddresses()
    {
        if (!$this->installAddress('SE', 'DIBS_SWEEDEN_ADDRESS_ID')) {
            return false;
        }

        if (!$this->installAddress('NO', 'DIBS_NORWAY_ADDRESS_ID')) {
            return false;
        }

        if (!$this->installAddress('DK', 'DIBS_DENMARK_ADDRESS_ID')) {
            return false;
        }

        return true;
    }

    protected function uninstallDefaultAddresses()
    {
        $addressConfigs = [
            'DIBS_SWEEDEN_ADDRESS_ID',
            'DIBS_NORWAY_ADDRESS_ID',
            'DIBS_DENMARK_ADDRESS_ID',
        ];

        foreach ($addressConfigs as $addressConfig) {
            $idAddress = $this->configurationAdapter->get($addressConfig);
            if (!$idAddress) {
                return true;
            }

            $address = new Address($idAddress);
            $address->delete();
        }

        return true;
    }

    private function installAddress($countryIso, $countryAddressConfig)
    {
        $idCountry = Country::getByIso($countryIso);
        $country = new Country($idCountry);

        if (is_array($country->name)) {
            $countryName = reset($country->name);
        } else {
            $countryName = $country->name;
        }

        $address = new Address();
        $address->id_country = $country->id;
        $address->alias = sprintf('Dibs Easy %s', $countryName);
        $address->address1 = 'Address1';
        $address->address2 = '';
        $address->postcode = '00000';
        $address->city = 'Any';
        $address->firstname = 'Dibs';
        $address->lastname = 'Easy';
        $address->phone = '000000000';
        $address->id_customer = 0;
        $address->deleted = 0;

        if (!$address->save()) {
            throw new Exception('Failed to save default address');
        }

        $this->configurationAdapter->set($countryAddressConfig, $address->id);

        return true;
    }
}
