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
}
