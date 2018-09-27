<?php
/**
 * kreXX: Krumo eXXtended
 *
 * kreXX is a debugging tool, which displays structured information
 * about any PHP object. It is a nice replacement for print_r() or var_dump()
 * which are used by a lot of PHP developers.
 *
 * kreXX is a fork of Krumo, which was originally written by:
 * Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author
 *   brainworXX GmbH <info@brainworxx.de>
 *
 * @license
 *   http://opensource.org/licenses/LGPL-2.1
 *
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
 *
 *   This library is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 2.1 of the License, or (at
 *   your option) any later version.
 *   This library is distributed in the hope that it will be useful, but WITHOUT
 *   ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 *   FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser General Public License
 *   for more details.
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this library; if not, write to the Free Software Foundation,
 *   Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace Brainworxx\Krexx\Service\Plugin;

/**
 * Allow plugins to alter the configuration
 *
 * @api
 *
 * @package Brainworxx\Krexx\Service
 */
class Registration
{
    const IS_ACTIVE = 'isActive';
    const CONFIG_CLASS = 'configClass';
    const PLUGIN_NAME = 'name';
    const PLUGIN_VERSION = 'ver';

    /**
     * The registered plugin configuration files as class names.
     *
     * @var array
     */
    protected static $plugins = array();

    /**
     * The configured chunk folder from the plugin.
     *
     * @var string
     */
    protected static $chunkFolder;

    /**
     * The configures log folder from the plugin.
     *
     * @var string
     */
    protected static $logFolder;

    /**
     * The configured configuration file from the plugin.
     *
     * @var string
     */
    protected static $configFile;

    /**
     * Blacklist of forbidden debug methods.
     *
     * @var array
     */
    protected static $blacklistDebugMethods = array();

    /**
     * Blacklist of classes, that will never get debug-method-called.
     *
     * @var array
     */
    protected static $blacklistDebugClass = array();

    /**
     * Additinal help files with text for the debugger.
     *
     * @var array
     */
    protected static $additionalHelpFiles = array();

    /**
     * List of all class rewritesfor the factory.
     *
     * @var array
     */
    protected static $rewriteList = array();

    /**
     * List of all registered events for the event handler.
     *
     * @var array
     */
    protected static $eventList = array();

    /**
     * Setter for the path to the configuration file.
     *
     * @api
     *
     * @param $path
     *   The absolute path to the configuration file.
     */
    public static function setConfigFile($path)
    {
        static::$configFile = $path;
    }

    /**
     * Setter for the path to the chaunks folder.
     *
     * @api
     *
     * @param $path
     *   The absolute path to the chunks folder.
     */
    public static function setChunksFolder($path)
    {
        static::$chunkFolder = $path;
    }

    /**
     * Setter for the log folder.
     *
     * @api
     *
     * @param $path
     *   The absolute path to the log folder.
     */
    public static function setLogFolder($path)
    {
        static::$logFolder = $path;
    }

    /**
     * Add a class / method to the debug method blacklist
     *
     * @api
     *
     * @param string $class
     *   The class, where  the method is hosted,
     * @param string $methodName
     *   The name of the method.
     */
    public static function addMethodToDebugBlacklist($class, $methodName)
    {
        if (isset(static::$blacklistDebugMethods[$class]) === false) {
            static::$blacklistDebugMethods[$class] = array();
        }
        if (in_array($methodName, static::$blacklistDebugMethods[$class]) === false) {
            static::$blacklistDebugMethods[$class][] = $methodName;
        }
    }

    /**
     * Add a class / method to the debug method blacklist
     *
     * @api
     *
     * @param string $class
     *   The class name that gets blacklisted.
     */
    public static function addClassToDebugBlacklist($class)
    {
        if (in_array($class, static::$blacklistDebugMethods) === false) {
            static::$blacklistDebugMethods[] = $class;
        }
    }

    /**
     * Adding a single overwrite class for the factory.
     *
     * Wrapper arround Factory::$rewrite[].
     *
     * @api
     *
     * @param string $originalClass
     * @param string $rewriteClass
     */
    public static function addRewrite($originalClass, $rewriteClass)
    {
        static::$rewriteList[$originalClass] = $rewriteClass;
    }

    /**
     * Register an event handler.
     *
     * @api
     *
     * @param string $name
     *   The event name
     * @param string $className
     *   The class name.
     */
    public static function registerEvent($name, $className)
    {
        if (isset(static::$eventList[$name]) === false) {
            static::$eventList[$name] = array();
        }
        static::$eventList[$name][$className] = $className;
    }

    /**
     * Register an additional help file.
     *
     * You can also overwrite existing texts here.
     *
     * @api
     *
     * @param string $path
     */
    public static function registerAdditionalHelpFile($path)
    {
        static::$additionalHelpFiles[] = $path;
    }
    /**
     * Register a plugin.
     *
     * @param string $configClass
     *   The class name of the configuration class for this plugin.
     *   Must extend the \Brainworxx\Krexx\Service\AbstractPluginConfig
     */
    public static function register($configClass)
    {
        static::$plugins[$configClass] = array(
            static::CONFIG_CLASS => $configClass,
            static::IS_ACTIVE => false,
            static::PLUGIN_NAME => call_user_func(array($configClass, 'getName')),
            static::PLUGIN_VERSION => call_user_func(array($configClass, 'getVersion'))
        );
    }

    /**
     * We activate the plugin with the name, and execute its configuration method.
     *
     * @param string $configClass
     *   The class name of the configuration class for this plugin.
     */
    public static function activatePlugin($configClass)
    {
        if (isset(static::$plugins[$configClass])) {
            static::$plugins[$configClass][static::IS_ACTIVE] = true;
            /** @var \Brainworxx\Krexx\Service\Plugin\PluginConfigInterface $staticPlugin */
            $staticPlugin = static::$plugins[$configClass][static::CONFIG_CLASS];
            $staticPlugin::exec();

            if (isset(\Krexx::$pool)) {
                // Update stuff in the pool.
                \Krexx::$pool->rewrite = static::$rewriteList;
                \Krexx::$pool->eventService->register = static::$eventList;
                \Krexx::$pool->messages->readHelpTexts();
            }
        }
        // No registration, no config, no plugin.
        // Do nothing.
    }

    /**
     * We deactivate the plugin and reset the configuration
     *
     * @param string $configClass
     *   The name of the plugin.
     */
    public static function deactivatePlugin($configClass)
    {
        if (static::$plugins[$configClass][static::IS_ACTIVE] !== true) {
            // We will not purge everything for a already deactivated plugin.
            return;
        }

        // Purge all settings in the underlying registration class.
        static::$logFolder = '';
        static::$chunkFolder = '';
        static::$configFile = '';
        static::$blacklistDebugMethods = array();
        static::$blacklistDebugClass = array();
        static::$additionalHelpFiles = array();
        static::$eventList = array();
        static::$rewriteList = array();

        // Go through the remaining plugins.
        static::$plugins[$configClass][static::IS_ACTIVE] = false;
        foreach (static::$plugins as $pluginName => $plugin) {
            if ($plugin[static::IS_ACTIVE]) {
                call_user_func(array(static::$plugins[$pluginName][static::CONFIG_CLASS], 'exec'));
            }
        }

        // Renew the configration class, so the new one will load all settings
        // from the registration class.
        if (isset(\Krexx::$pool)) {
            \Krexx::$pool->rewrite = static::$rewriteList;
            \Krexx::$pool->eventService->register = static::$eventList;
            \Krexx::$pool->config = \Krexx::$pool->createClass('Brainworxx\\Krexx\\Service\\Config\\Config');
            \Krexx::$pool->messages->readHelpTexts();
        }
    }
}
