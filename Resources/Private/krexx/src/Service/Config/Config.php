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
 *   kreXX Copyright (C) 2014-2019 Brainworxx GmbH
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

use Brainworxx\Krexx\Service\Config\From\Cookie;
use Brainworxx\Krexx\Service\Config\From\Ini;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;

/**
 * Access the debug settings here.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Config extends Fallback
{

    const REMOTE_ADDRESS = 'REMOTE_ADDR';
    const CHUNKS_FOLDER = 'chunks';
    const LOG_FOLDER = 'log';
    const CONFIG_FOLDER = 'config';

    /**
     * Our current settings.
     *
     * @var Model[]
     */
    public $settings = [];

    /**
     * List of all configured debug methods.
     *
     * @var array
     */
    public $debugFuncList = [];

    /**
     * Our security handler.
     *
     * @deprecated
     *   Since 3.1.0. Will be removed.
     *
     * @var Security
     */
    public $security;

    /**
     * Validating configuration settings.
     *
     * @var Validation
     */
    public $validation;

    /**
     * Our ini file configuration handler.
     *
     * @deprecated
     *   Since 3.1.0. Will be set to protected.
     *
     * @var Ini
     */
    public $iniConfig;

    /**
     * Our cookie configuration handler.
     *
     * @deprecated
     *   Since 3.1.0. Will be set to protected.
     *
     * @var Cookie
     */
    public $cookieConfig;

    /**
     * Here we store the paths to our files and directories.
     *
     * @var array
     */
    protected $directories = [];

    /**
     * Has kreXX been disabled via php call \Krexx::disable()?
     *
     * @var bool
     */
    public static $disabledByPhp = false;

    /**
     * Inject the pool and load the configuration.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        // Point the configuration to the right directories
        $this->directories = [
            static::CHUNKS_FOLDER => SettingsGetter::getChunkFolder(),
            static::LOG_FOLDER => SettingsGetter::getLogFolder(),
            static::CONFIG_FOLDER => SettingsGetter::getConfigFile(),
        ];

        $this->security = $pool->createClass(Security::class);
        $this->validation = $pool->createClass(Validation::class);
        $pool->config = $this;

        $this->iniConfig = $pool->createClass(Ini::class)
            ->loadIniFile($this->getPathToIniFile());
        $this->cookieConfig = $pool->createClass(Cookie::class);

        // Loading the settings.
        foreach ($this->configFallback as $settings) {
            foreach ($settings as $name) {
                $this->loadConfigValue($name);
            }
        }

        // We may need to change the disabling again, in case we are in cli
        // or ajax mode and have no fileoutput.
        if ($this->isRequestAjaxOrCli() === true &&
            $this->getSetting(static::SETTING_DESTINATION) !==  static::VALUE_FILE
        ) {
            // No kreXX for you. At least until you start forced logging.
            $this->setDisabled(true);
        }

        // Now that our settings are in place, we need to check the
        // ip to decide if we need to deactivate kreXX.
        if ($this->isAllowedIp($this->getSetting(static::SETTING_IP_RANGE)) === false) {
            // No kreXX for you! At all.
            $this->setDisabled(true);
            static::$disabledByPhp = true;
        }

        $this->debugFuncList = explode(',', $this->getSetting(static::SETTING_DEBUG_METHODS));
    }

    /**
     * Setter for the enabling from sourcecode.
     *
     * @param bool $value
     *   Whether it it enabled, or not.
     */
    public function setDisabled($value)
    {
        $this->settings[static::SETTING_DISABLED]
            ->setValue($value)
            ->setSource('Internal flow');
    }

    /**
     * Returns the developer handle from the cookies.
     *
     * @return string
     *   The Developer handle.
     */
    public function getDevHandler()
    {
        static $handle;

        if ($handle === null) {
            $handle = $this->cookieConfig->getConfigFromCookies('deep', static::SETTING_DEV_HANDLE);
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
     * @param string $name
     *   The name of the config value.
     *
     * @return $this
     *   Return this, for chaining.
     */
    public function loadConfigValue($name)
    {
        $isEditable = $this->iniConfig->getFeIsEditable($name);
        $section = $this->feConfigFallback[$name][static::SECTION];

        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->setSection($section)
            ->setEditable($isEditable)
            ->setType($this->feConfigFallback[$name][static::RENDER][static::RENDER_TYPE]);

        // Do we accept cookie settings here?
        if ($isEditable === true) {
            $cookieSetting = $this->cookieConfig->getConfigFromCookies($section, $name);
            // Do we have a value in the cookies?
            if ($cookieSetting  !== null &&
                ($name === static::SETTING_DISABLED && $cookieSetting === static::VALUE_FALSE) === false
            ) {
                // We must not overwrite a disabled=true with local cookie settings!
                // Otherwise it could get enabled locally, which might be a security
                // issue.
                $model->setValue($cookieSetting)->setSource('Local cookie settings');
                $this->settings[$name] = $model;
                return $this;
            }
        }

        // Do we have a value in the ini?
        $iniSettings = $this->iniConfig->getConfigFromFile($section, $name);
        if ($iniSettings === null) {
            // Take the factory settings.
            $model->setValue($this->feConfigFallback[$name][static::VALUE])->setSource('Factory settings');
            $this->settings[$name] = $model;
            return $this;
        }

        $model->setValue($iniSettings)->setSource('Krexx.ini settings');
        $this->settings[$name] = $model;

        return $this;
    }

    /**
     * Check if the current request is an AJAX request.
     *
     * @return bool
     *   TRUE when this is AJAX, FALSE if not
     */
    protected function isRequestAjaxOrCli()
    {
        $server = $this->pool->getServer();

        if (isset($server['HTTP_X_REQUESTED_WITH']) === true &&
            strtolower($server['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest' &&
            $this->getSetting(static::SETTING_DETECT_AJAX) === true
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
        return $this->directories[static::CHUNKS_FOLDER];
    }

    /**
     * Get the path to the logging directory.
     *
     * @return string
     *   The absolute path, trailed by the '/'
     */
    public function getLogDir()
    {
        return $this->directories[static::LOG_FOLDER];
    }

    /**
     * Get the path to the configuration file.
     *
     * @return string
     *   The absolute path to the Krexx.ini.
     */
    public function getPathToIniFile()
    {
        return $this->directories[static::CONFIG_FOLDER];
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
        $server = $this->pool->getServer();

        if (empty($server[static::REMOTE_ADDRESS]) === true) {
            $remote = '';
        } else {
            $remote = $server[static::REMOTE_ADDRESS];
        }

        $whitelist = explode(',', $whitelist);
        if (php_sapi_name() === 'cli' || in_array($remote, $whitelist) === true) {
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
     * Determines if the specific class is blacklisted for debug methods.
     *
     * @param object $data
     *   The class we are analysing.
     *
     * @deprecated
     *   Sinde 3.1.0. Will be removed
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return bool
     *   Whether the function is allowed to be called.
     */
    public function isAllowedDebugCall($data)
    {
        return $this->validation->isAllowedDebugCall($data);
    }

    /**
     * Get the name of the skin render class
     *
     * @return string
     */
    public function getSkinClass()
    {
        return $this->skinConfiguration[$this->getSetting(static::SETTING_SKIN)][static::SKIN_CLASS];
    }

     /**
     * Get the path to the skin html files.
     *
     * @return string
     */
    public function getSkinDirectory()
    {
        return $this->skinConfiguration[$this->getSetting(static::SETTING_SKIN)][static::SKIN_DIRECTORY];
    }

    /**
     * Simply return a list of all skins (as their configuration keys.
     *
     * @return array
     */
    public function getSkinList()
    {
        return array_keys($this->skinConfiguration);
    }
}
