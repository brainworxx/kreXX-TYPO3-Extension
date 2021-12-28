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

namespace Brainworxx\Krexx\Service\Config;

use Brainworxx\Krexx\Service\Config\From\Cookie;
use Brainworxx\Krexx\Service\Config\From\File;
use Brainworxx\Krexx\Service\Config\From\Ini;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\View\Output\CheckOutput;

/**
 * Access the debug settings here.
 */
class Config extends Fallback
{
    /**
     * Our current settings.
     *
     * @var Model[]
     */
    public $settings = [];

    /**
     * List of all configured debug methods.
     *
     * @var string[]
     */
    public $debugFuncList = [];

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
     *   Since 4.1.0. Will be removed. Use $fileConfig instead.
     *
     * @var Ini
     */
    protected $iniConfig;

    /**
     * Our file configuration handler.
     *
     * @var File
     */
    protected $fileConfig;

    /**
     * Our cookie configuration handler.
     *
     * @var Cookie
     */
    protected $cookieConfig;

    /**
     * Here we store the paths to our files and directories.
     *
     * @var array
     */
    protected $directories = [];

    /**
     * @var CheckOutput
     */
    protected $checkOutput;

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

        $this->validation = $pool->createClass(Validation::class);
        $pool->config = $this;

        $this->iniConfig = $this->fileConfig = $pool->createClass(File::class)
            ->loadFile($this->getPathToConfigFile());
        $this->cookieConfig = $pool->createClass(Cookie::class);

        // Loading the settings.
        foreach ($this->configFallback as $settings) {
            foreach ($settings as $name) {
                $this->loadConfigValue($name);
            }
        }

        // We may need to change the disabling again, in case we are in cli
        // or ajax mode and have no file output.
        $this->checkOutput = $pool->createClass(CheckOutput::class);
        $this->debugFuncList = explode(',', $this->getSetting(static::SETTING_DEBUG_METHODS));

        $this->checkEnabledStatus();
    }

    /**
     * Check if kreXX can be enabled or not.
     */
    protected function checkEnabledStatus()
    {
        if (
            $this->getSetting(static::SETTING_DESTINATION) !==  static::VALUE_FILE &&
            ($this->checkOutput->isAjax() === true || $this->checkOutput->isCli() === true)
        ) {
            // No kreXX for you. At least until you start forced logging.
            $this->setDisabled(true);
        }

        // Now that our settings are in place, we need to check the
        // ip to decide if we need to deactivate kreXX.
        if ($this->checkOutput->isAllowedIp($this->getSetting(static::SETTING_IP_RANGE)) === false) {
            // No kreXX for you! At all.
            $this->setDisabled(true);
            static::$disabledByPhp = true;
        }
    }

    /**
     * Setter for the enabling from sourcecode.
     *
     * @param bool $value
     *   Whether it is enabled, or not.
     */
    public function setDisabled(bool $value)
    {
        $this->settings[static::SETTING_DISABLED]
            ->setValue($value)
            ->setSource('Internal flow');
    }

    /**
     * Wrapper around the stored settings array, to intercept settings calls.
     *
     * @param string $name
     *   The name of the setting.
     *
     * @return int|bool|string|null
     *   The setting.
     */
    public function getSetting(string $name)
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
    public function loadConfigValue(string $name): Config
    {
        $model = $this->prepareModelWithFeSettings($name);
        $section = $model->getSection();

        // Do we accept cookie settings here?
        if ($model->getEditable() === true) {
            $cookieSetting = $this->cookieConfig->getConfigFromCookies($section, $name);
            // Do we have a value in the cookies?
            if (
                $cookieSetting  !== null &&
                ($name === static::SETTING_DISABLED && $cookieSetting === static::VALUE_FALSE) === false
            ) {
                // We must not overwrite a disabled=true with local cookie settings!
                // Otherwise, it could get enabled locally, which might be a security
                // issue.
                $model->setValue($cookieSetting)->setSource('Local cookie settings');
                $this->settings[$name] = $model;
                return $this;
            }
        }

        // Do we have a value in the configuration file?
        $fileSettings = $this->fileConfig->getConfigFromFile($section, $name);
        if ($fileSettings === null) {
            // Take the factory settings.
            $model->setValue($this->feConfigFallback[$name][static::VALUE])->setSource('Factory settings');
            $this->settings[$name] = $model;
            return $this;
        }

        $model->setValue($fileSettings)->setSource('Configuration file settings');
        $this->settings[$name] = $model;

        return $this;
    }

    /**
     * Get the path to the chunks' directory.
     *
     * @return string
     *   The absolute path, trailed by the '/'
     */
    public function getChunkDir(): string
    {
        return $this->directories[static::CHUNKS_FOLDER];
    }

    /**
     * Get the path to the logging directory.
     *
     * @return string
     *   The absolute path, trailed by the '/'
     */
    public function getLogDir(): string
    {
        return $this->directories[static::LOG_FOLDER];
    }

    /**
     * Get the path to the configuration file.
     *
     * @deprecated
     *   Since 4.1.0. Will be removed. Use getPathToConfigFile
     *
     * @codeCoverageIgnore
     *   We will not test deprecated methods.
     *
     * @return string
     *   The absolute path to the Krexx.ini.
     */
    public function getPathToIniFile(): string
    {
        return $this->getPathToConfigFile();
    }

    /**
     * Get the path to the configuration file.
     *
     * @return string
     *   The absolute path to the configuration file.
     */
    public function getPathToConfigFile(): string
    {
        return $this->directories[static::CONFIG_FOLDER];
    }

    /**
     * The path to the config file that is used.
     *
     * @param string $file
     *   The file path.
     */
    public function setPathToConfigFile(string $file)
    {
        $this->directories[static::CONFIG_FOLDER] = $file;
    }

    /**
     * Get the name of the skin render class
     *
     * @return string
     */
    public function getSkinClass(): string
    {
        return $this->skinConfiguration[$this->getSetting(static::SETTING_SKIN)][static::SKIN_CLASS];
    }

     /**
     * Get the path to the skin html files.
     *
     * @return string
     */
    public function getSkinDirectory(): string
    {
        return $this->skinConfiguration[$this->getSetting(static::SETTING_SKIN)][static::SKIN_DIRECTORY];
    }

    /**
     * Simply return a list of all skins as their configuration keys.
     *
     * @return array
     */
    public function getSkinList(): array
    {
        return array_keys($this->skinConfiguration);
    }

    /**
     * Create the model with the fe editing settings.
     *
     * @param string $name
     *   Name of the setting.
     *
     * @return \Brainworxx\Krexx\Service\Config\Model
     *   The prepared model.
     */
    protected function prepareModelWithFeSettings(string $name): Model
    {
        $fileFeSettings = $this->fileConfig->getFeConfigFromFile($name);

        if ($fileFeSettings === null) {
            // Use the fallback values.
            $fileFeSettings = $this->feConfigFallback[$name][static::RENDER];
        }
        $section = $this->feConfigFallback[$name][static::SECTION];

        return $this->pool->createClass(Model::class)
            ->setSection($section)
            ->setEditable($fileFeSettings[static::RENDER_EDITABLE] === static::VALUE_TRUE)
            ->setType($fileFeSettings[static::RENDER_TYPE]);
    }
}
