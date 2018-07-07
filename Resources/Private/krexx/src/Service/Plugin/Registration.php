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

use Brainworxx\Krexx\Service\Factory\Factory;

/**
 * Register, activate and deactivate plugins.
 *
 * @package Brainworxx\Krexx\Service
 */
class Registration
{
    /**
     * The registered plugin configuration files as class names.
     *
     * @var array
     */
    protected static $plugins = array();

    const IS_ACTIVE = 'isActive';
    const CONFIG_CLASS = 'configClass';

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
            static::IS_ACTIVE => false
        );
    }

    /**
     * We activate the plugin with the name, and execute its configuration method.
     *
     * @param string $name
     *   The name of the plugin.
     */
    public static function activatePlugin($name)
    {
        if (isset(static::$plugins[$name])) {
            static::$plugins[$name][static::IS_ACTIVE] = true;
            /** @var \Brainworxx\Krexx\Service\Plugin\PluginConfigInterface $staticPlugin */
            $staticPlugin = static::$plugins[$name][static::CONFIG_CLASS];
            $staticPlugin::exec();
        }
        // No registration, no config, no plugin.
        // Do nothing.
    }

    /**
     * We deactivate the plugin and reset the configuration
     *
     * @param string $name
     *   The name of the plugin.
     */
    public static function deactivatePlugin($name)
    {
        // Purge the rewrites.
        Factory::$rewrite = array();
        // Purge the event registration.
        \Krexx::$pool->eventService->purge();

        // Go through the remaining plugins.
        static::$plugins[$name][static::IS_ACTIVE] = false;
        foreach (static::$plugins as $plugin) {
            if ($plugin[static::IS_ACTIVE]) {
                /** @var \Brainworxx\Krexx\Service\Plugin\PluginConfigInterface $staticPlugin */
                $staticPlugin = static::$plugins[$name][static::CONFIG_CLASS];
                $staticPlugin::exec();
            }
        }
    }
}
