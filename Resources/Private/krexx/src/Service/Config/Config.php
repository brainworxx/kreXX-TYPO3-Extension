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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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
    public array $settings = [];

    /**
     * Validating configuration settings.
     *
     * @var Validation
     */
    public Validation $validation;

    /**
     * Our file configuration handler.
     *
     * @var File
     */
    protected File $fileConfig;

    /**
     * Our cookie configuration handler.
     *
     * @var Cookie
     */
    protected Cookie $cookieConfig;

    /**
     * Here we store the paths to our files and directories.
     *
     * @var string[]
     */
    protected array $directories = [];

    /**
     * @var CheckOutput
     */
    protected CheckOutput $checkOutput;

    /**
     * Has kreXX been disabled via php call \Krexx::disable()?
     *
     * @var bool
     */
    public static bool $disabledByPhp = false;

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

        $this->fileConfig = $pool->createClass(File::class)
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
        $this->pool->messages->setLanguageKey($this->getSetting(static::SETTING_LANGUAGE_KEY));

        $this->checkEnabledStatus();
    }

    /**
     * Check if kreXX can be enabled or not.
     */
    protected function checkEnabledStatus(): void
    {
        if (
            $this->getSetting(static::SETTING_DESTINATION) !==  static::VALUE_FILE &&
            ($this->checkOutput->isAjax() || $this->checkOutput->isCli())
        ) {
            // No kreXX for you. At least until you start forced logging.
            $this->setDisabled(true);
        }

        // Now that our settings are in place, we need to check the
        // ip to decide if we need to deactivate kreXX.
        if (!$this->checkOutput->isAllowedIp($this->getSetting(static::SETTING_IP_RANGE))) {
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
    public function setDisabled(bool $value): void
    {
        $this->settings[static::SETTING_DISABLED]
            ->setValue($value)
            ->setSource($this->pool->messages->getHelp('internalFlow'));
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
     * Are we allowed to use this value for this setting from the cookies?
     *
     * @param \Brainworxx\Krexx\Service\Config\Model $model
     *   The configuration model, loaded with the fe editing values.
     * @param string $name
     *   Name of the configuration.
     * @param string|null $value
     *   Value of the configuration.
     * @return bool
     */
    protected function isCookieValueAllowed(Model $model, string $name, ?string $value): bool
    {
        if ($value === null || !$model->isEditable()) {
            // We either have no value, or are not allowed to edit it in the first place.
            return false;
        }

        if ($name === static::SETTING_DISABLED && $value === static::VALUE_FALSE) {
            // We must not overwrite a disabled=true with local cookie settings!
            // Otherwise, it could get enabled locally, which might be a security
            // issue.
            return false;
        }

        return true;
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
        $this->settings[$name] = $model;

        $cookieSetting = $this->cookieConfig->getConfigFromCookies($section, $name);
        if ($this->isCookieValueAllowed($model, $name, $cookieSetting)) {
            $model->setValue($cookieSetting)
                ->setSource($this->pool->messages->getHelp('localCookieSettings'));
            return $this;
        }

        // Do we have a value in the configuration file?
        if ($this->fileConfig->getConfigFromFile($section, $name) !== null) {
            $model->setValue($this->fileConfig->getConfigFromFile($section, $name))
                ->setSource($this->pool->messages->getHelp('configFileSettings'));
            return $this;
        }

        // Plugin overwrites
        if (isset(SettingsGetter::getNewFallbackValues()[$name])) {
            $model->setValue(SettingsGetter::getNewFallbackValues()[$name])
                ->setSource($this->pool->messages->getHelp('pluginOverwriteSetting'));
            return $this;
        }

        // Fallback the factory settings.
        $model->setValue($this->feConfigFallback[$name][static::VALUE])
            ->setSource($this->pool->messages->getHelp('factorySetting'));

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
        return $this->directories[static::CHUNKS_FOLDER] ?? '';
    }

    /**
     * Get the path to the logging directory.
     *
     * @return string
     *   The absolute path, trailed by the '/'
     */
    public function getLogDir(): string
    {
        return $this->directories[static::LOG_FOLDER] ?? '';
    }

    /**
     * Get the path to the configuration file.
     *
     * @return string
     *   The absolute path to the configuration file.
     */
    public function getPathToConfigFile(): string
    {
        return $this->directories[static::CONFIG_FOLDER] ?? '';
    }

    /**
     * The path to the config file that is used.
     *
     * @param string $file
     *   The file path.
     */
    public function setPathToConfigFile(string $file): void
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
     * @return string[]
     */
    public function getSkinList(): array
    {
        $keys = array_keys($this->skinConfiguration);
        return array_combine($keys, $keys);
    }

    /**
     * Return the list of available languages.
     *
     * @return string[]
     */
    public function getLanguageList(): array
    {
        return array_merge(
            ['en' => 'English', 'de' => 'Deutsch'],
            SettingsGetter::getAdditionalLanguages()
        );
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
        $fileFeSettings = $this->fileConfig->getFeConfigFromFile($name) ??
            $this->feConfigFallback[$name][static::RENDER];

        return $this->pool->createClass(Model::class)
            ->setSection($this->feConfigFallback[$name][static::SECTION])
            ->setEditable($fileFeSettings[static::RENDER_EDITABLE] === static::VALUE_TRUE)
            ->setType($fileFeSettings[static::RENDER_TYPE]);
    }
}
