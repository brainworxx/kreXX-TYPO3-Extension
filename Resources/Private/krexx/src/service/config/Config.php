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
use Brainworxx\Krexx\Service\Config\From\Cookie;
use Brainworxx\Krexx\Service\Config\From\Ini;

/**
 * Access the debug settings here.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Config extends Fallback
{

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
     * Our security handler.
     *
     * @var Security
     */
    public $security;

    /**
     * Our ini file configuration handler.
     *
     * @var Ini
     */
    public $iniConfig;

    /**
     * Our cookie configuration handler.
     *
     * @var Cookie
     */
    public $cookieConfig;

    /**
     * Here we store the paths to our files and directories.
     *
     * @var array
     */
    protected $directories = array();

    /**
     * Injection the pool and loading the configuration.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        $this->initDirectories();
        $this->security = $pool->createClass('Brainworxx\\Krexx\\Service\\Config\\Security');
        $this->iniConfig = $pool->createClass('Brainworxx\\Krexx\\Service\\Config\\From\\Ini')
            ->loadIniFile($this->getPathToIniFile());
        $this->cookieConfig = $pool->createClass('Brainworxx\\Krexx\\Service\\Config\\From\\Cookie');

        // Loading the settings.
        foreach ($this->configFallback as $section => $settings) {
            foreach ($settings as $name => $factorySetting) {
                $this->loadConfigValue($section, $name, $factorySetting);
            }
        }

        // We may need to change the disabling again, in case we are in cli
        // or ajax mode and have no fileoutput.
        if ($this->isRequestAjaxOrCli() &&
            $this->getSetting('destination') !== 'file'
        ) {
            // No kreXX for you!
            $this->setDisabled(true);
        }

        // Now that our settings are in place, we need to check the
        // ip to decide if we need to deactivate kreXX.
        if (!$this->isAllowedIp($this->getSetting('iprange'))) {
            // No kreXX for you!
            $this->setDisabled(true);
        }

        $this->debugFuncList = explode(',', $this->getSetting('debugMethods'));
    }

    protected function initDirectories()
    {
        $overwrites = $this->pool->getGlobals('kreXXoverwrites');

        if (empty($overwrites['directories']['chunks'])) {
            $this->directories['chunks'] = $this->pool->krexxDir . 'chunks' . DIRECTORY_SEPARATOR;
        } else {
            $this->directories['chunks'] = $overwrites['directories']['chunks'] . DIRECTORY_SEPARATOR;
        }

        if (empty($overwrites['directories']['log'])) {
            $this->directories['log'] = $this->pool->krexxDir . 'log' . DIRECTORY_SEPARATOR;
        } else {
            $this->directories['log'] = $overwrites['directories']['log'] . DIRECTORY_SEPARATOR;
        }

        if (empty($overwrites['directories']['config'])) {
            $this->directories['config'] = $this->pool->krexxDir . 'config' . DIRECTORY_SEPARATOR . 'Krexx.ini';
        } else {
            $this->directories['config'] = $overwrites['directories']['config'] .
                DIRECTORY_SEPARATOR . 'Krexx.ini';
        }
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
        static $handle = false;

        if ($handle === false) {
            $handle = $this->cookieConfig->getConfigFromCookies('deep', 'devHandle');
        }

        return $handle;
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
     * Load values of the kreXX's configuration.
     *
     * @param string $section
     *   The group inside the ini of the value that we want to read.
     * @param string $name
     *   The name of the config value.
     * @param string $factorySetting
     *   The factory setting
     */
    protected function loadConfigValue($section, $name, $factorySetting)
    {
        $feConfig = $this->iniConfig->getFeConfig($name);
        /** @var Model $model */
        $model = $this->pool->createClass('Brainworxx\\Krexx\\Service\\Config\\Model')
            ->setSection($section)
            ->setEditable($feConfig[0])
            ->setType($feConfig[1]);

        // Do we accept cookie settings here?
        if ($feConfig[0] === true) {
            $cookieSetting = $this->cookieConfig->getConfigFromCookies($section, $name);
            // Do we have a value in the cookies?
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
        }

        // Do we have a value in the ini?
        $iniSettings = $this->iniConfig->getConfigFromFile($section, $name);
        if (isset($iniSettings)) {
            $model->setValue($iniSettings)->setSource('Krexx.ini settings');
            $this->settings[$name] = $model;
            return;
        }

        // Nothing yet? Give back factory settings.
        $model->setValue($factorySetting)->setSource('Factory settings');
        $this->settings[$name] = $model;
        return;
    }

    /**
     * Check if the current request is an AJAX request.
     *
     * @return bool
     *   TRUE when this is AJAX, FALSE if not
     */
    protected function isRequestAjaxOrCli()
    {
        $server = $this->pool->getGlobals('_SERVER');

        if (isset($server['HTTP_X_REQUESTED_WITH']) &&
            strtolower($server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
            $this->getSetting('detectAjax')
        ) {
            // Appending stuff after a ajax request will most likely
            // cause a js error. But there are moments when you actually
            // want to do this.
            //
            // We were supposed to detect ajax, and we did it right now.
            return true;
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
        return $this->directories['chunks'];

    }

    /**
     * Get the path to the logging directory.
     *
     * @return string
     *   The absolute path, trailed by the '/'
     */
    public function getLogDir()
    {
        return $this->directories['log'];
    }

    /**
     * Checks if the current client ip is allowed.
     *
     * @author Chin Leung
     * @see https://stackoverflow.com/questions/35559119/php-ip-address-whitelist-with-wildcards
     *
     * @param string $whitelist
     *   The ip whitelist.
     *
     * @return bool
     *   Whether the current client ip is allowed or not.
     */
    protected function isAllowedIp($whitelist)
    {
        $server = $this->pool->getGlobals('_SERVER');

        if (empty($server['REMOTE_ADDR'])) {
            $remote = '';
        } else {
            $remote = $server['REMOTE_ADDR'];
        }

        $whitelist = explode(',', $whitelist);
        if (php_sapi_name() === 'cli' || in_array($remote, $whitelist)) {
            // Either the IP is matched, or we are in CLI
            return true;
        }

        // Check the wildcards.
        foreach ($whitelist as $ip) {
            $ip = trim($ip);
            $wildcardPos = strpos($ip, '*');
            // Check if the ip has a wildcard.
            if ($wildcardPos !== false && substr($remote, 0, $wildcardPos) . '*' === $ip) {
                return true;
            }
        }

        return false;
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
    public function isAllowedDebugCall($data, $call)
    {
        // Check if the class itself is blacklisted.
        foreach ($this->classBlacklist as $classname) {
            if (is_a($data, $classname)) {
                // No debug methods for you.
                return false;
            }
        }

        // Check for a class / method combination.
        foreach ($this->methodBlacklist as $classname => $methodLlist) {
            if (is_a($data, $classname) && in_array($call, $methodLlist)) {
                // We have a winner, this one is blacklisted!
                return false;
            }
        }
        // Nothing found?
        return true;
    }

    /**
     * Get the path to the configuration file.
     *
     * @return string
     *   The absolute path to the Krexx.ini.
     */
    public function getPathToIniFile()
    {
        return $this->directories['config'];
    }
}
