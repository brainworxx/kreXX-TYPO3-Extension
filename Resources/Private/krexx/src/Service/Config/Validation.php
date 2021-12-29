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

use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Closure;
use ReflectionGenerator;
use ReflectionType;
use Reflector;

/**
 * Validation stuff for the configuration.
 */
class Validation extends Fallback
{
    /**
     * Part of a key for the messaging system.
     *
     * @var string
     */
    protected const KEY_CONFIG_ERROR = 'configError';

    /**
     * Part of a key for the messaging system.
     *
     * @var string
     */
    protected const KEY_CONFIG_ERROR_BOOL = 'configErrorBool';

    /**
     * Part of a key for the messaging system.
     *
     * @var string
     */
    protected const KEY_CONFIG_ERROR_INT = 'configErrorInt';

    /**
     * Part of a key for the messaging system.
     *
     * @var string
     */
    protected const KEY_CONFIG_ERROR_DEBUG_INVALID = 'configErrorDebugInvalid';

    /**
     * Pre-configuration which setting will never be editable.
     *
     * @var string[]
     */
    protected const FE_DO_NOT_EDIT = [
        self::SETTING_DESTINATION,
        self::SETTING_MAX_FILES,
        self::SETTING_DEBUG_METHODS,
        self::SETTING_IP_RANGE,
    ];

    /**
     * Loaded preconfiguration which setting will never be editable.
     *
     * @var string[]
     */
    protected $feDoNotEdit = [];

    /**
     * Known Problems with debug functions, which will most likely cause a fatal.
     *
     * @see \Brainworxx\Krexx\Service\Config\Config::isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Service\Plugin\Registration::addMethodToDebugBlacklist()
     *
     * @var array[]
     */
    protected $methodBlacklist = [];

    /**
     * These classes will never be polled by debug methods, because that would
     * most likely cause a fatal.
     *
     * @see \Brainworxx\Krexx\Service\Config\Security->isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects->pollAllConfiguredDebugMethods()
     *
     * @var string[]
     */
    protected $classBlacklist = [
        // Fun with reflection classes. Not really.
        ReflectionType::class,
        ReflectionGenerator::class,
        Reflector::class,
    ];

    /**
     * Setting the pool and retrieving the debug method blacklist.
     *
     * @param \Brainworxx\Krexx\Service\Factory\Pool $pool
     */
    public function __construct(Pool $pool)
    {
        parent::__construct($pool);

        $this->methodBlacklist = SettingsGetter::getBlacklistDebugMethods();
        $this->classBlacklist = array_merge(
            $this->classBlacklist,
            SettingsGetter::getBlacklistDebugClass()
        );

        // Load the settings for the do-not-edit config.
        $this->feDoNotEdit = static::FE_DO_NOT_EDIT;

        // Adding the new configuration options from the plugins.
        $pluginConfig = SettingsGetter::getNewSettings();
        if (empty($pluginConfig) === true) {
            return;
        }

        foreach ($pluginConfig as $newSetting) {
            if ($newSetting->isFeProtected() === true) {
                $this->feDoNotEdit[] = $newSetting->getName();
            }
        }
    }

    /**
     * Evaluate a single setting from the cookies or the ini file.
     *
     * @param string $group
     *   The group value in the ini.
     * @param string $name
     *   The name of the setting.
     * @param string|int|bool|null $value
     *   The value to evaluate.
     *
     * @return bool
     *   If it was evaluated.
     */
    public function evaluateSetting(string $group, string $name, $value): bool
    {
        if ($group === static::SECTION_FE_EDITING) {
            // These settings can never be changed in the frontend.
            return !in_array($name, $this->feDoNotEdit);
        }

        // We simply call the configured evaluation method.
        $callback = $this->feConfigFallback[$name][static::EVALUATE];
        if ($callback instanceof Closure) {
            return $callback($value, $this->pool);
        }

        return $this->$callback($value, $name, $group);
    }

    /**
     * We check the configuration for this skin.
     *
     * @param string|int|bool|null $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalSkin($value, string $name): bool
    {
        $result = isset($this->skinConfiguration[$value]) &&
            class_exists($this->skinConfiguration[$value][static::SKIN_CLASS]) &&
            $this->pool->fileService->fileIsReadable(
                $this->skinConfiguration[$value][static::SKIN_DIRECTORY] . 'header.html'
            );

        if ($result === false) {
            $this->pool->messages->addMessage(static::KEY_CONFIG_ERROR . ucfirst($name));
        }

        return $result;
    }

    /**
     * We are expecting 'browser' or 'file'.
     *
     * @param string|int|bool|null $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalDestination($value, string $name): bool
    {
        $result = ($value === static::VALUE_BROWSER
            || $value === static::VALUE_FILE
            || $value === static::VALUE_BROWSER_IMMEDIATELY
        );
        if ($result === false) {
            $this->pool->messages->addMessage(static::KEY_CONFIG_ERROR . ucfirst($name));
        }

        return $result;
    }

    /**
     * Evaluating the IP range, by testing that it is not empty.
     *
     * @param string|int|bool|null $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalIpRange($value, string $name): bool
    {
        $result = empty($value);
        if ($result === true) {
            $this->pool->messages->addMessage(static::KEY_CONFIG_ERROR . ucfirst($name));
        }

        return !$result;
    }

    /**
     * Evaluation the maximum runtime, by looking at the server settings, as
     * well as checking for an integer value.
     *
     * @param string|int|bool|null $value
     *   The value we want to evaluate
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalMaxRuntime($value, string $name, string $group): bool
    {
        $maxTime = (int)ini_get('max_execution_time');

        if ($maxTime <= 0) {
            // There is no max execution time set.
            // We ignore the max execution time on the shell anyway.
            return true;
        }

        $result = true;
        if ($this->evalInt($value, $name, $group) === false || $maxTime < (int)$value) {
            $this->pool->messages->addMessage(
                static::KEY_CONFIG_ERROR . ucfirst($name) . 'Big',
                [$maxTime]
            );

            $result = false;
        }

        return $result;
    }

    /**
     * Evaluates a string of 'true' or 'false'.
     *
     * @param string|int|bool|null $value
     *   The string we want to evaluate.
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalBool($value, string $name, string $group): bool
    {
        $result = ($value === static::VALUE_TRUE || $value === static::VALUE_FALSE || is_bool($value));
        if ($result === false) {
            $this->pool->messages->addMessage(static::KEY_CONFIG_ERROR_BOOL, [$group, $name]);
        }

        return $result;
    }

    /**
     * Evaluates a string as integer.
     *
     * It must be greater than 0 and smaller than 101.
     *
     * @param string|int|bool|null $value
     *   The string we want to evaluate.
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalInt($value, string $name, string $group): bool
    {
        $result = ((int) $value) > 0;
        if ($result === false) {
            $this->pool->messages->addMessage(static::KEY_CONFIG_ERROR_INT, [$group, $name]);
        }

        return $result;
    }

    /**
     * Sanity check, if the supplied debug methods are not obviously flawed.
     *
     * @param string|int|bool|null $value
     *   Comma separated list of debug methods.
     * @param string $name
     *   The name of the value we are checking, needed for the feedback text.
     * @param string $group
     *   The name of the group that we are evaluating, needed for the feedback
     *   text.
     *
     * @return bool
     *   Whether it does evaluate or not.
     */
    protected function evalDebugMethods($value, string $name, string $group): bool
    {
        $list = explode(',', $value);

        foreach ($list as $entry) {
            // Test for whitespace.
            if (strpos($entry, ' ') !== false) {
                $this->pool->messages->addMessage(
                    static::KEY_CONFIG_ERROR_DEBUG_INVALID,
                    [$group, $name, $entry]
                );
                return false;
            }
        }

        return true;
    }

    /**
     * Determines if the specific class is blacklisted for debug methods.
     *
     * @param object $data
     *   The class we are analysing.
     * @param string $method
     *   The method that we want to call.
     *
     * @return bool
     *   Whether the function is allowed to be called.
     */
    public function isAllowedDebugCall($data, string $method): bool
    {
        // Check if the class itself is blacklisted.
        foreach ($this->classBlacklist as $classname) {
            if ($data instanceof $classname) {
                // No debug methods for you.
                return false;
            }
        }

        // Check if the combination of class and method is blacklisted.
        foreach ($this->methodBlacklist as $classname => $debugMethod) {
            if ($data instanceof $classname && in_array($method, $debugMethod, true) === true) {
                return false;
            }
        }

        // Nothing found?
        return true;
    }
}
