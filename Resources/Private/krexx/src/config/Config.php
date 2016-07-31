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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Config;

use Brainworxx\Krexx\Framework\Toolbox;

/**
 * Debug settings access.
 *
 * @package Brainworxx\Krexx\Framework
 */
class Config extends Tools
{
    /**
     * Setter for the enabling from sourcecode.
     *
     * Will only set it to true, if the
     *
     * @param bool $state
     */
    public static function setEnabled($state)
    {
        self::$isEnabled = $state;
    }

    /**
     * Get\Set kreXX state: whether it is enabled or disabled.
     *
     * @return bool
     *   Returns whether kreXX is enabled or not.
     */
    public static function getEnabled()
    {
        // Disabled in the ini or in the local settings?
        if (Config::getConfigValue('runtime', 'disabled') == 'true') {
            // self::$isEnabled = FALSE;
            return false;
        }

        // Check for ajax and cli.

        if (Toolbox::isRequestAjaxOrCli()) {
            return false;
        }

        // We will only return the real value, if there are no other,
        // more important settings.
        return self::$isEnabled;
    }

    /**
     * Returns values from kreXX's configuration.
     *
     * @param string $group
     *   The group inside the ini of the value that we want to read.
     * @param string $name
     *   The name of the config value.
     *
     * @return string
     *   The value.
     */
    public static function getConfigValue($group, $name)
    {
        // Do some caching.
        if (isset(self::$localConfig[$group][$name])) {
            return self::$localConfig[$group][$name];
        }

        // Do we have a value in the cookies?
        $localSetting = self::getConfigFromCookies($group, $name);
        // Do we have a value in the ini?
        $iniSettings = self::getConfigFromFile($group, $name);
        if (isset($localSetting)) {
            // We must not overwrite a disabled=true with local cookie settings!
            // Otherwise it could get enabled locally,
            // which might be a security issue.
            if (($name == 'disabled' && $localSetting == 'false') ||
                ($name == 'destination' && !is_null($iniSettings))) {
                // Do nothing.
                // We ignore this setting.
            } else {
                self::$localConfig[$group][$name] = $localSetting;
                return $localSetting;
            }
        }


        if (isset($iniSettings)) {
            self::$localConfig[$group][$name] = $iniSettings;
            return $iniSettings;
        }

        // Nothing yet? Give back factory settings.
        self::$localConfig[$group][$name] = self::$configFallback[$group][$name];
        return self::$configFallback[$group][$name];
    }

    /**
     * Here we overwrite the local settings.
     *
     * When we are handling errors and are analysing objects, we should
     * output protected and private variables of a class, outputting as
     * much info as possible.
     *
     * @param array $newSettings
     *   Part of the array we want to overwrite.
     */
    public static function overwriteLocalSettings(array $newSettings)
    {
        self::arrayMerge(self::$localConfig, $newSettings);
    }

    /**
     * Returns the whole configuration as an array.
     *
     * The source of the value (factory, ini or cookie)
     * is also included. We need this one for the display
     * on the frontend.
     * We display here the invalid settings (if we have
     * any,so the user can correct it.
     *
     * @return array
     *   The configuration with the source.
     */
    public static function getWholeConfiguration()
    {
        // We may have some project settings in the ini
        // as well as some in the cookies, but some may be missing.
        $source = array();
        $config = array();
        $cookieConfig = array();

        // Get Settings from the cookies. We do not correct them,
        // so the dev can correct them, in case there are wrong values.
        if (isset($_COOKIE['KrexxDebugSettings'])) {
            $cookieConfig = json_decode($_COOKIE['KrexxDebugSettings'], true);
        }

        // We must remove the cookie settings for which we do not accept
        // any values. They might contain wrong values.
        foreach ($cookieConfig as $name => $data) {
            $paramConfig = FeConfig::getFeConfig($name);
            if ($paramConfig[0] === false) {
                // We act as if we have not found the value. Configurations that are
                // not editable on the frontend will be ignored!
                unset($cookieConfig[$name]);
            }
        }

        // Get Settings from the ini file.
        $configini = (array)parse_ini_string(Toolbox::getFileContents(self::getPathToIni()), true);

        // Overwrite the settings from the fallback.
        foreach (self::$configFallback as $sectionName => $sectionData) {
            foreach ($sectionData as $parameterName => $parameterValue) {
                // Get cookie settings.
                if (isset($cookieConfig[$parameterName])) {
                    // We check them, if they are correct. Normally, we would do this,
                    // when we get the value via self::getConfigFromCookies(), but we
                    // should feedback the dev about the settings.
                    self::evaluateSetting('', $parameterName, $cookieConfig[$parameterName]);
                    $config[$sectionName][$parameterName] = htmlspecialchars($cookieConfig[$parameterName]);
                    $source[$sectionName][$parameterName] = 'local cookie settings';
                } else {
                    // File settings.
                    if (isset($configini[$sectionName][$parameterName])) {
                        $config[$sectionName][$parameterName] = htmlspecialchars(
                            $configini[$sectionName][$parameterName]
                        );
                        $source[$sectionName][$parameterName] = 'Krexx ini settings';
                        continue;
                    } else {
                        // Nothing yet? Return factory settings.
                        $config[$sectionName][$parameterName] = $parameterValue;
                        $source[$sectionName][$parameterName] = 'factory settings';
                    }
                }
            }
        }

        $result = array(
            $source,
            $config,
        );
        return $result;
    }

    /**
     * Returns the developer handle from the cookies.
     *
     * @return string
     *   The Developer handle.
     */
    public static function getDevHandler()
    {
        return self::getConfigFromCookies('deep', 'Local open function');
    }

    /**
     * Gets the path to the ini file.
     *
     * In typo3, it is not a good idea to store the config
     * settings inside the module directory. When an update is
     * triggered, all settings will be lost. So wen need a functionality
     * to point kreXX to another directory for it's config.
     *
     * @return string
     *   The path to the ini file.
     */
    public static function getPathToIni()
    {
        if (!isset(self::$pathToIni)) {
            $configini = (array)parse_ini_string(Toolbox::getFileContents(self::$krexxdir . 'KrexxConfig.ini'), true);
            if (isset($configini['pathtoini']['pathtoini'])) {
                self::$pathToIni = $configini['pathtoini']['pathtoini'];
            } else {
                self::$pathToIni = self::$krexxdir . 'Krexx.ini';
            }
        }
        return self::$pathToIni;
    }

    /**
     * Setter for the path to the ini file.
     *
     * Useful, if you don't want to save your ini file in the kreXX directory.
     *
     * @param string $path
     *   The path to the ini file
     */
    public static function setPathToIni($path)
    {
        self::$pathToIni = $path;
    }

    /**
     * Determines if a debug function is blacklisted in s specific class.
     *
     * @param object $data
     *   The class we are analysing.
     * @param string $call
     *   The function name we want to call.
     *
     * @return bool
     *   Whether the function is allowed to be called.
     */
    public static function isAllowedDebugCall($data, $call)
    {

        foreach (self::$debugMethodsBlacklist as $classname => $method) {
            if (is_a($data, $classname) && $call == $method) {
                // We have a winner, this one is blacklisted!
                return false;
            }
        }
        // Nothing found?
        return true;
    }

    /**
     * Gets a list of all available skins for the frontend config.
     *
     * @return array
     *   An array with the skinnames.
     */
    public static function getSkinList()
    {
        // Static cache to make it a little bit faster.
        static $list = array();

        if (count($list) == 0) {
            // Get the list.
            $list = array_filter(glob(self::$krexxdir . 'resources/skins/*'), 'is_dir');
            // Now we need to filter it, we only want the names, not the full path.
            foreach ($list as &$path) {
                $path = str_replace(self::$krexxdir . 'resources/skins/', '', $path);
            }
        }

        return $list;
    }
}
