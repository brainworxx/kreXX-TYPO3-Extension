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
 *   kreXX Copyright (C) 2014-2021 Brainworxx GmbH
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

declare(strict_types=1);

namespace Brainworxx\Krexx\Service\Plugin;

/**
 * Interfacing with the data supplied by the plugins.
 */
class SettingsGetter extends Registration
{
    /**
     * Getter for the configured configuration file
     *
     * @return string
     *   Absolute path to the configuration file.
     */
    public static function getConfigFile(): string
    {
        if (empty(static::$configFile)) {
            static::$configFile = KREXX_DIR . 'config' . DIRECTORY_SEPARATOR . 'Krexx.';
        }

        return static::$configFile;
    }

    /**
     * Setter for the path to the chunks' folder.
     *
     * @return string
     *   The absolute path to the chunks' folder.
     */
    public static function getChunkFolder(): string
    {
        if (empty(static::$chunkFolder)) {
            static::$chunkFolder = KREXX_DIR . 'chunks' . DIRECTORY_SEPARATOR;
        }

        return static::$chunkFolder;
    }

    /**
     * Getter for the logfolder.
     *
     * @return string
     *   The absolute path to the log folder.
     */
    public static function getLogFolder(): string
    {
        if (empty(static::$logFolder)) {
            static::$logFolder = KREXX_DIR . 'log' . DIRECTORY_SEPARATOR;
        }

        return static::$logFolder;
    }

    /**
     * Getter for the blacklisted debug methods.
     *
     * @return array[]
     *   The debug methods.
     */
    public static function getBlacklistDebugMethods(): array
    {
        return static::$blacklistDebugMethods;
    }

    /**
     * Getter for the blacklisted debug method classes.
     *
     * @return string[]
     *   The list with classes.
     */
    public static function getBlacklistDebugClass(): array
    {
        return static::$blacklistDebugClass;
    }

    /**
     * Getter for the rewrites.
     *
     * key = original class name.
     * value = new class name.
     *
     * @return string[]
     *   The rewrites.
     */
    public static function getRewriteList(): array
    {
        return static::$rewriteList;
    }

    /**
     * Getter for the event list.
     *
     * @return array[]
     *   The event list.
     */
    public static function getEventList(): array
    {
        return static::$eventList;
    }

    /**
     * Getter for additional help files, absolute path.
     *
     * @return string[]
     *   List of these files
     */
    public static function getAdditionalHelpFiles(): array
    {
        return static::$additionalHelpFiles;
    }

    /**
     * Get the status of all registered plugins.
     *
     * @return \Brainworxx\Krexx\Service\Plugin\PluginConfigInterface[][]
     *   The configuration data for the view
     */
    public static function getPlugins(): array
    {
        return static::$plugins;
    }

    /**
     * Getter for the skins, provided by plugins.
     *
     * @return array[]
     *   The configuration arrays of additional skins.
     */
    public static function getAdditionalSkinList(): array
    {
        return static::$additionalSkinList;
    }

    /**
     * Getter for all the registered class names that can do a scalar analysis.
     *
     * @return string[]
     *   List of the class names.
     */
    public static function getAdditionalScalarString(): array
    {
        return static::$additionalScalarString;
    }

    /**
     * Getter for the list of class instances that contain new settings
     * definitions.
     *
     * @return \Brainworxx\Krexx\Service\Plugin\NewSetting[]
     */
    public static function getNewSettings(): array
    {
        return static::$newSettings;
    }
}
