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
 * Interfacing with the data supplied by the plugins.
 *
 * @internal
 *
 * @package Brainworxx\Krexx\Service\Plugin
 */
class SettingsGetter extends Registration
{



    /**
     * Getter for the configured configuration file
     *
     * @internal
     *
     * @return string
     *   Absolute path to the configuration file.
     */
    public static function getConfigFile()
    {
        if (empty(static::$configFile)) {
            static::$configFile = KREXX_DIR . 'config/Krexx.ini';
        }

        return static::$configFile;
    }

    /**
     * Setter for the path to the chunks folder.
     *
     * @internal
     *
     * @return string
     *   The absolute path to the chunks folder.
     */
    public static function getChunkFolder()
    {
        if (empty(static::$chunkFolder)) {
            static::$chunkFolder = KREXX_DIR . 'chunks/';
        }

        return static::$chunkFolder;
    }

    /**
     * Getter for the logfolder.
     *
     * @internal
     *
     * @return string
     *   The absolute path to the log folder.
     */
    public static function getLogFolder()
    {
        if (empty(static::$logFolder)) {
            static::$logFolder = KREXX_DIR . 'log/';
        }

        return static::$logFolder;
    }

    /**
     * Getter for the blacklisted debug methods.
     *
     * @internal
     *
     * @return array
     *   The debug methods.
     */
    public static function getBlacklistDebugMethods()
    {
        return static::$blacklistDebugMethods;
    }

    /**
     * Getter for the blacklisted debug method classes.
     *
     * @internal
     *
     * @return array
     *   THe liost with classes.
     */
    public static function getBlacklistDebugClass()
    {
        return static::$blacklistDebugClass;
    }

    /**
     * Getter for the rewrites.
     *
     * key = original class name.
     * value = new class name.
     *
     * @internal
     *
     * @return array
     *   The rewrites.
     */
    public static function getRewriteList()
    {
        return static::$rewriteList;
    }

    /**
     * Getter for the event list.
     *
     * @internal
     *
     * @return array
     *   The event list.
     */
    public static function getEventList()
    {
        return static::$eventList;
    }

    /**
     * Getter for additional help files, absolute path.
     *
     * @internal
     *
     * @return array
     *   List of these files
     */
    public static function getAdditionelHelpFiles()
    {
        return static::$additionalHelpFiles;
    }

    /**
     * Get the status of all registered plugins.
     *
     * @internal
     *
     * @return array
     *   The configuration data for the view
     */
    public static function getPlugins()
    {
        return static::$plugins;
    }
}
