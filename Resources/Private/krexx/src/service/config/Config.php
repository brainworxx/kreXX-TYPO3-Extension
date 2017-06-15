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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

namespace Brainworxx\Krexx\Service\Config;

use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * Access the debug settings here.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Config extends Security
{

    /**
     * The current position of our iterator array.
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Our current settings.
     *
     * @var Model[]
     */
    public $settings = array();

    /**
     * List of all configured debug methods.
     *
     * @var array
     */
    public $debugFuncList = array();

    /**
     * Injection the pool and loading the configuration.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);
        // Loading the settings.
        foreach ($this->configFallback as $section => $settings) {
            foreach ($settings as $name => $setting) {
                $this->loadConfigValue($section, $name);
            }
        }

        // Now that our settings are in place, we need to check the
        // ip to decide if we need to deactivate kreXX.
        if (!$this->isAllowedIp($this->getSetting('iprange'))) {
            // No kreXX for you!
            $this->setDisabled(true);
        }

        // We may need to change the disabling again, in case we are in cli
        // or ajax mode and have no fileoutput.
        if ($this->isRequestAjaxOrCli()) {
            // No kreXX for you!
            $this->setDisabled(true);
        }

        $this->debugFuncList = explode(',', $this->getSetting('debugMethods'));
    }

    /**
     * Setter for the enabling from sourcecode.
     *
     * @param bool $value
     *   Whether it it enabled, or not.
     */
    public function setDisabled($value)
    {
        $this->settings['disabled']
            ->setValue($value)
            ->setSource('Internal flow');
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
    public function overwriteLocalSettings(array $newSettings)
    {
        foreach ($newSettings as $name => $value) {
            $this->settings[$name]->setValue($value);
        }
    }

    /**
     * Returns the developer handle from the cookies.
     *
     * @return string
     *   The Developer handle.
     */
    public function getDevHandler()
    {
        return $this->getConfigFromCookies('deep', 'devHandle');
    }

    /**
     * Wrapper around the stored settings array, to intercept settings calls.
     *
     * @param string $name
     *   The name of the setting.
     *
     * @return string|null
     *   The setting.
     */
    public function getSetting($name)
    {
        return $this->settings[$name]->getValue();
    }

    /**
     * Get the configuration of the frontend config form.
     *
     * @param string $parameterName
     *   The parameter you want to render.
     *
     * @return array
     *   The configuration (is it editable, a dropdown, a textfield, ...)
     */
    protected function getFeConfig($parameterName)
    {
        static $config = array();

        if (!isset($config[$parameterName])) {
            // Load it from the file.
            $filevalue = $this->getFeConfigFromFile($parameterName);
            if (!is_null($filevalue)) {
                $config[$parameterName] = $filevalue;
            }
        }

        // Do we have a value?
        if (isset($config[$parameterName])) {
            $type = $config[$parameterName]['type'];
            $editable = $config[$parameterName]['editable'];
        } else {
            // Fallback to factory settings.
            if (isset($this->feConfigFallback[$parameterName])) {
                $type = $this->feConfigFallback[$parameterName]['type'];
                $editable = $this->feConfigFallback[$parameterName]['editable'];
            } else {
                // Unknown parameter.
                $type = 'None';
                $editable = 'false';
            }
        }
        if ($editable === 'true') {
            $editable = true;
        } else {
            $editable = false;
        }

        return array($editable, $type);
    }

    /**
     * Load values of the kreXX's configuration.
     *
     * @param string $section
     *   The group inside the ini of the value that we want to read.
     * @param string $name
     *   The name of the config value.
     */
    protected function loadConfigValue($section, $name)
    {
        $feConfig = $this->getFeConfig($name);
        /** @var Model $model */
        $model = $this->pool->createClass('Brainworxx\\Krexx\\Service\\Config\\Model')
            ->setSection($section)
            ->setEditable($feConfig[0])
            ->setType($feConfig[1]);

        // Do we have a value in the cookies?
        $cookieSetting = $this->getConfigFromCookies($section, $name);
        if (!is_null($cookieSetting)) {
            // We must not overwrite a disabled=true with local cookie settings!
            // Otherwise it could get enabled locally, which might be a security
            // issue.
            if (($name === 'disabled' && $cookieSetting === 'false')) {
                // Do nothing.
                // We ignore this setting.
            } else {
                $model->setValue($cookieSetting)->setSource('Local cookie settings');
                $this->settings[$name] = $model;
                return;
            }
        }

        // Do we have a value in the ini?
        $iniSettings = $this->getConfigFromFile($section, $name);
        if (isset($iniSettings)) {
            $model->setValue($iniSettings)->setSource('Krexx.ini settings');
            $this->settings[$name] = $model;
            return;
        }

        // Nothing yet? Give back factory settings.
        $model->setValue($this->configFallback[$section][$name])->setSource('Factory settings');
        $this->settings[$name] = $model;
        return;
    }

    /**
     * Get the config of the frontend config form from the file.
     *
     * @param string $parameterName
     *   The parameter you want to render.
     *
     * @return array
     *   The configuration (is it editable, a dropdown, a textfield, ...)
     */
    public function getFeConfigFromFile($parameterName)
    {
        static $config = array();

        // Loaded?
        if (isset($config[$parameterName])) {
            return $config[$parameterName];
        }

        // Get the human readable stuff from the ini file.
        $value = $this->getConfigFromFile('feEditing', $parameterName);

        // Is it set?
        if (!is_null($value)) {
            // We need to translate it to a "real" setting.
            // Get the html control name.
            switch ($parameterName) {
                case 'maxfiles':
                    $type = 'Input';
                    break;

                default:
                    // Nothing special, we get our value from the config class.
                    $type = $this->feConfigFallback[$parameterName]['type'];
            }
            // Stitch together the setting.
            switch ($value) {
                case 'none':
                    $type = 'None';
                    $editable = 'false';
                    break;

                case 'display':
                    $editable = 'false';
                    break;

                case 'full':
                    $editable = 'true';
                    break;

                default:
                    // Unknown setting.
                    // Fallback to no display, just in case.
                    $type = 'None';
                    $editable = 'false';
                    break;
            }
            $result = array(
                'type' => $type,
                'editable' => $editable,
            );
            // Remember the setting.
            $config[$parameterName] = $result;
        }

        // Do we have a value now?
        if (isset($config[$parameterName])) {
            return $config[$parameterName];
        }

        // Still here?
        return null;
    }

    /**
     * Returns settings from the ini file.
     *
     * @param string $group
     *   The group name inside of the ini.
     * @param string $name
     *   The name of the setting.
     *
     * @return string
     *   The value from the file.
     */
    public function getConfigFromFile($group, $name)
    {
        static $config = array();

        // Not loaded?
        if (empty($config)) {
            $pathToIni = $this->getPathToIniFile();
            if (is_file($pathToIni)) {
                $config = (array)parse_ini_string(
                    $this->pool->fileService->getFileContents($this->getPathToIniFile()),
                    true
                );
            }
            if (empty($config)) {
                // Still empty means that there is no ini file. We add a dummy.
                // This will prevent the failing reload of the ini file.
                $config[] = 'dummy';
            }
        }

        // Do we have a value in the ini?
        if (isset($config[$group][$name]) && $this->evaluateSetting($group, $name, $config[$group][$name])) {
            return $config[$group][$name];
        }
        return null;
    }

    /**
     * Returns settings from the local cookies.
     *
     * @param string $group
     *   The name of the group inside the cookie.
     * @param string $name
     *   The name of the value.
     *
     * @return string|null
     *   The value.
     */
    protected function getConfigFromCookies($group, $name)
    {
        static $config = array();

        // Not loaded?
        if (empty($config)) {
            if (isset($_COOKIE['KrexxDebugSettings'])) {
                // We have local settings.
                $setting = json_decode($_COOKIE['KrexxDebugSettings'], true);
                if (is_array($setting)) {
                    $config = $setting;
                }
            }
        }

        $paramConfig = $this->getFeConfig($name);
        if ($paramConfig[0] === false) {
            // We act as if we have not found the value. Configurations that are
            // not editable on the frontend will be ignored!
            return null;
        }


        // Do we have a value in the cookies?
        if (isset($config[$name]) && $this->evaluateSetting($group, $name, $config[$name])) {
            // We escape them, just in case.
            return htmlspecialchars($config[$name]);
        }

        // Still here?
        return null;
    }

    /**
     * Check if the current request is an AJAX request.
     *
     * @return bool
     *   TRUE when this is AJAX, FALSE if not
     */
    protected function isRequestAjaxOrCli()
    {
        if ($this->getSetting('destination') !== 'file') {
            // Fileoutout does not respect ajax or cli, because there is no
            // interference with the output.
            return false;
        }

        // Check for ajax.
        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) &&
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'
        ) {
            // Appending stuff after a ajax request will most likely
            // cause a js error. But there are moments when you actually
            // want to do this.
            if ($this->getSetting('detectAjax')) {
                // We were supposed to detect ajax, and we did it right now.
                return true;
            }
        }
        // Check for CLI.
        if (php_sapi_name() === 'cli') {
            return true;
        }

        // Still here? This means it's neither.
        return false;
    }

    /**
     * Get the path to the chunks directory.
     *
     * @return string
     *   The absolute path, trailed by the '/'
     */
    public function getChunkDir()
    {
        if (empty($GLOBALS['kreXXoverwrites']['directories']['chunks'])) {
            // Return the standard settings.
            return $this->pool->krexxDir . 'chunks' . DIRECTORY_SEPARATOR;
        }
        // Return the Overwrites
        return $GLOBALS['kreXXoverwrites']['directories']['chunks'] . DIRECTORY_SEPARATOR;

    }

    /**
     * Get the path to the logging directory.
     *
     * @return string
     *   The absolute path, trailed by the '/'
     */
    public function getLogDir()
    {
        if (empty($GLOBALS['kreXXoverwrites']['directories']['log'])) {
            // Return the standard settings.
            return $this->pool->krexxDir . 'log' . DIRECTORY_SEPARATOR;
        }
        // Return the Overwrites
        return $GLOBALS['kreXXoverwrites']['directories']['log'] . DIRECTORY_SEPARATOR;
    }

    /**
     * Get the path to the configuration file.
     *
     * @return string
     *   The absolute path to the Krexx.ini.
     */
    public function getPathToIniFile()
    {
        if (empty($GLOBALS['kreXXoverwrites']['directories']['config'])) {
            // Return the standard settings.
            return $this->pool->krexxDir . 'config' . DIRECTORY_SEPARATOR . 'Krexx.ini';
        }
        // Return the Overwrites
        return $GLOBALS['kreXXoverwrites']['directories']['config'] . DIRECTORY_SEPARATOR . 'Krexx.ini';
    }

    /**
     * Checks if the current client ip is allowed.
     *
     * @param string $whitelist
     *   The ip whitelist.
     *
     * @return bool
     *   Whether the current client ip is allowed or not.
     */
    protected function isAllowedIp($whitelist)
    {
        if (empty($_SERVER['REMOTE_ADDR'])) {
            $remote = '';
        } else {
            $remote = $_SERVER['REMOTE_ADDR'];
        }

        // Fallback to the Chin Leung implementation.
        // @author Chin Leung
        // @see https://stackoverflow.com/questions/35559119/php-ip-address-whitelist-with-wildcards
        $whitelist = explode(',', $whitelist);
        if (in_array($remote, $whitelist)) {
            // If the ip is matched, return true.
            return true;
        }

        // Check the wildcards.
        foreach ($whitelist as $ip) {
            $ip = trim($ip);
            $wildcardPos = strpos($ip, '*');
            # Check if the ip has a wildcard
            if ($wildcardPos !== false && substr($remote, 0, $wildcardPos) . '*' === $ip) {
                return true;
            }
        }

        return false;
    }
}
