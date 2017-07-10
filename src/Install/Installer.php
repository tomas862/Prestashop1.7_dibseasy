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

namespace Invertus\Dibs\Install;

use Db;
use Dibs;
use Exception;
use Invertus\Dibs\Adapter\ConfigurationAdapter;
use Invertus\Dibs\Adapter\LanguageAdapter;
use Invertus\Dibs\Adapter\ToolsAdapter;
use OrderState;

/**
 * Class Installer
 *
 * @package Invertus\Dibs\Install
 */
class Installer
{
    /**
     * @var Dibs
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
     * @param Dibs $module
     * @param ConfigurationAdapter $configurationAdapter
     * @param LanguageAdapter $languageAdapter
     * @param ToolsAdapter $toolsAdapter
     * @param Db $db
     * @param array $config
     */
    public function __construct(
        Dibs $module,
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
            // skip order state configuration
            // since those will be saved after order states are created
            if (false !== strpos($name, 'ORDER_STATE')) {
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
        $sqlStatements = $this->getSqlStatements($this->module->getLocalPath().'sql/install.sql');

        return $this->db->execute($sqlStatements);
    }

    /**
     * Uninstall database
     *
     * @return bool
     */
    protected function uninstallDatabase()
    {
        $sqlStatements = $this->getSqlStatements($this->module->getLocalPath().'sql/uninstall.sql');

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
}
