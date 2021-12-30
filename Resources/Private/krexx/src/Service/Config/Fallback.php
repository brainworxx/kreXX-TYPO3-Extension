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
use Brainworxx\Krexx\View\Skins\RenderHans;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;

/**
 * Configuration fallback settings.
 *
 * We have so much of them, they need an own class.
 */
abstract class Fallback implements ConfigConstInterface
{
    /**
     * The fallback configuration.
     *
     * @var string[][]
     */
    protected const CONFIG_FALLBACK = [
        self::SECTION_OUTPUT => [
            self::SETTING_DISABLED,
            self::SETTING_IP_RANGE,
            self::SETTING_DETECT_AJAX,
        ],
        self::SECTION_BEHAVIOR => [
            self::SETTING_SKIN,
            self::SETTING_DESTINATION,
            self::SETTING_MAX_FILES,
            self::SETTING_USE_SCOPE_ANALYSIS,
        ],
        self::SECTION_PRUNE => [
            self::SETTING_MAX_STEP_NUMBER,
            self::SETTING_ARRAY_COUNT_LIMIT,
            self::SETTING_NESTING_LEVEL,
        ],
        self::SECTION_PROPERTIES => [
            self::SETTING_ANALYSE_PROTECTED,
            self::SETTING_ANALYSE_PRIVATE,
            self::SETTING_ANALYSE_TRAVERSABLE,
            self::SETTING_ANALYSE_SCALAR,
        ],
        self::SECTION_METHODS => [
            self::SETTING_ANALYSE_PROTECTED_METHODS,
            self::SETTING_ANALYSE_PRIVATE_METHODS,
            self::SETTING_ANALYSE_GETTER,
            self::SETTING_DEBUG_METHODS,
        ],
        self::SECTION_EMERGENCY => [
            self::SETTING_MAX_CALL,
            self::SETTING_MAX_RUNTIME,
            self::SETTING_MEMORY_LEFT,
        ],
    ];

    /**
     * Render settings for an editable select field.
     *
     * @var string[]
     */
    protected const EDITABLE_SELECT = [
        self::RENDER_TYPE => self::RENDER_TYPE_SELECT,
        self::RENDER_EDITABLE => self::VALUE_TRUE,
    ];

    /**
     * Render settings for a display only input field.
     *
     * @var string[]
     */
    protected const DISPLAY_ONLY_INPUT = [
        self::RENDER_TYPE => self::RENDER_TYPE_INPUT,
        self::RENDER_EDITABLE => self::VALUE_FALSE,
    ];

    /**
     * Render settings for a editable input field.
     *
     * @var string[]
     */
    protected const EDITABLE_INPUT = [
        self::RENDER_TYPE => self::RENDER_TYPE_INPUT,
        self::RENDER_EDITABLE => self::VALUE_TRUE,
    ];

    /**
     * Render settings for a display only select field.
     *
     * @var string[]
     */
    protected const DISPLAY_ONLY_SELECT = [
        self::RENDER_TYPE => self::RENDER_TYPE_SELECT,
        self::RENDER_EDITABLE => self::VALUE_FALSE,
    ];

    /**
     * Render settings for a field which will not be displayed, or accept values.
     *
     * @var string[]
     */
    protected const DISPLAY_NOTHING = [
        self::RENDER_TYPE => self::RENDER_TYPE_NONE,
        self::RENDER_EDITABLE => self::VALUE_FALSE,
    ];

    /**
     * Defining the layout of the frontend editing form.
     *
     * @var string[][]
     */
    public $configFallback = [];

    /**
     * Values, rendering settings and the actual fallback value.
     *
     * @var string[][]
     */
    public $feConfigFallback = [];

    /**
     * The skin configuration.
     *
     * @var string[][]
     */
    protected $skinConfiguration = [];

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool and initialize the fallback configuration, get the skins.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        // Generate the configuration fallback.
        $this->generateConfigFallback();
        // Generate the configuration for the fe editor.
        $this->generateFeConfigFallback();
        // Generate the plugin configuration.
        $this->generatePluginConfig();
        // Setting up out two bundled skins.
        $this->generateSkinConfiguration();
    }

    /**
     * Generate the configuration fallback.
     */
    protected function generateConfigFallback(): void
    {
        $this->configFallback = static::CONFIG_FALLBACK;

        // Adding the new configuration options from the plugins.
        $pluginConfig = SettingsGetter::getNewSettings();
        if (empty($pluginConfig) === true) {
            return;
        }

        foreach ($pluginConfig as $newSetting) {
            if (isset($this->configFallback[$newSetting->getSection()]) === false) {
                $this->configFallback[$newSetting->getSection()] = [];
            }
            $this->configFallback[$newSetting->getSection()][] = $newSetting->getName();
        }
    }

    /**
     * Generate the frontend configuration fallback.
     */
    protected function generateFeConfigFallback(): void
    {
        $this->feConfigFallback = [
            static::SETTING_ANALYSE_PROTECTED_METHODS => $this->returnBoolSelectFalse(static::SECTION_METHODS),
            static::SETTING_ANALYSE_PRIVATE_METHODS => $this->returnBoolSelectFalse(static::SECTION_METHODS),
            static::SETTING_ANALYSE_PROTECTED => $this->returnBoolSelectFalse(static::SECTION_PROPERTIES),
            static::SETTING_ANALYSE_PRIVATE => $this->returnBoolSelectFalse(static::SECTION_PROPERTIES),
            static::SETTING_ANALYSE_SCALAR => $this->returnBoolSelectTrue(static::SECTION_PROPERTIES),
            static::SETTING_ANALYSE_TRAVERSABLE => $this->returnBoolSelectTrue(static::SECTION_PROPERTIES),
            static::SETTING_DEBUG_METHODS => $this->returnDebugMethods(),
            static::SETTING_NESTING_LEVEL => $this->returnInput(static::SECTION_PRUNE, 5),
            static::SETTING_MAX_CALL => $this->returnInput(static::SECTION_EMERGENCY, 10),
            static::SETTING_DISABLED => $this->returnBoolSelectFalse(static::SECTION_OUTPUT),
            static::SETTING_DESTINATION => $this->returnDestination(),
            static::SETTING_MAX_FILES => $this->returnMaxFiles(),
            static::SETTING_SKIN => $this->returnSkin(),
            static::SETTING_DETECT_AJAX => $this->returnBoolSelectTrue(static::SECTION_OUTPUT),
            static::SETTING_IP_RANGE => $this->returnIpRange(),
            static::SETTING_ANALYSE_GETTER => $this->returnBoolSelectTrue(static::SECTION_METHODS),
            static::SETTING_MEMORY_LEFT => $this->returnInput(static::SECTION_EMERGENCY, 64),
            static::SETTING_MAX_RUNTIME => $this->returnMaxRuntime(),
            static::SETTING_USE_SCOPE_ANALYSIS => $this->returnBoolSelectTrue(static::SECTION_BEHAVIOR),
            static::SETTING_MAX_STEP_NUMBER => $this->returnInput(static::SECTION_PRUNE, 10),
            static::SETTING_ARRAY_COUNT_LIMIT => $this->returnInput(static::SECTION_PRUNE, 300),
        ];
    }

    /**
     * Generate the plugin configuration, if available.
     */
    protected function generatePluginConfig(): void
    {
        // Adding the new configuration options from the plugins.
        $pluginConfig = SettingsGetter::getNewSettings();
        if (empty($pluginConfig) === true) {
            return;
        }

        foreach ($pluginConfig as $newSetting) {
            $this->feConfigFallback[$newSetting->getName()] = $newSetting->getFeSettings();
        }
    }

    /**
     * Generate the skin configuration.
     */
    protected function generateSkinConfiguration(): void
    {
        $this->skinConfiguration = array_merge(
            [
                static::SKIN_SMOKY_GREY => [
                    static::SKIN_CLASS => RenderSmokyGrey::class,
                    static::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/smokygrey/'
                ],
                static::SKIN_HANS => [
                    static::SKIN_CLASS => RenderHans::class,
                    static::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/hans/'
                ]
            ],
            SettingsGetter::getAdditionalSkinList()
        );
    }

    /**
     * Return the settings for a simple true/false select.
     *
     * @param string $section
     *   The section, where it belongs to
     *
     * @return array
     *   The settings.
     */
    protected function returnBoolSelectFalse(string $section): array
    {
        return [
            static::VALUE => static::VALUE_FALSE,
            static::RENDER => static::EDITABLE_SELECT,
            static::EVALUATE => static::EVAL_BOOL,
            static::SECTION => $section,
        ];
    }

    /**
     * Return the settings for a simple true/false select.
     *
     * @param string $section
     *   The section, where it belongs to
     *
     * @return array
     *   The settings.
     */
    protected function returnBoolSelectTrue(string $section): array
    {
        return [
            static::VALUE => static::VALUE_TRUE,
            static::RENDER => static::EDITABLE_SELECT,
            static::EVALUATE => static::EVAL_BOOL,
            static::SECTION => $section,
        ];
    }

    /**
     * The render settings for a simple input field.
     *
     * @param string $section
     *   The section, where it belongs to
     * @param int $value
     *   The prefilled value.
     *
     * @return array
     *   The settings.
     */
    protected function returnInput(string $section, int $value): array
    {
        return [
            static::VALUE => $value,
            static::RENDER => static::EDITABLE_INPUT,
            static::EVALUATE => static::EVAL_INT,
            static::SECTION => $section,
        ];
    }

    /**
     * The render settings for the debug methods.
     *
     * @return array
     */
    protected function returnDebugMethods(): array
    {
        return [
            // Debug methods that get called.
            // A debug method must be public and have no parameters.
            // Change these only if you know what you are doing.
            static::VALUE => static::VALUE_DEBUG_METHODS,
            static::RENDER => static::DISPLAY_ONLY_INPUT,
            static::EVALUATE => static::EVAL_DEBUG_METHODS,
            static::SECTION =>  static::SECTION_METHODS,
        ];
    }

    /**
     * The render settings for the destination.
     *
     * @return array
     */
    protected function returnDestination(): array
    {
        return [
            // Either 'file', 'browser' or 'browserImmediately'.
            static::VALUE => static::VALUE_BROWSER,
            static::RENDER => static::DISPLAY_ONLY_SELECT,
            static::EVALUATE => static::EVAL_DESTINATION,
            static::SECTION => static::SECTION_BEHAVIOR,
        ];
    }

    /**
     * The render settings for the max files.
     *
     * @return array
     */
    protected function returnMaxFiles(): array
    {
        return [
            // Maximum files that are kept inside the logfolder.
            static::VALUE => 10,
            static::RENDER => static::DISPLAY_ONLY_INPUT,
            static::EVALUATE => static::EVAL_INT,
            static::SECTION => static::SECTION_BEHAVIOR,
        ];
    }

    /**
     * The render settings for the skin.
     *
     * @return array
     */
    protected function returnSkin(): array
    {
        return [
            static::VALUE => static::SKIN_SMOKY_GREY,
            static::RENDER => static::EDITABLE_SELECT,
            static::EVALUATE => static::EVAL_SKIN,
            static::SECTION => static::SECTION_BEHAVIOR,
        ];
    }

    /**
     * The render settings for the ip range.
     *
     * @return array
     */
    protected function returnIpRange(): array
    {
        return [
            // IP range for calling kreXX.
            // kreXX is disabled for everyone who dies not fit into this range.
            static::VALUE => '*',
            static::RENDER => static::DISPLAY_NOTHING,
            static::EVALUATE => static::EVAL_IP_RANGE,
            static::SECTION => static::SECTION_OUTPUT,
        ];
    }

    /**
     * The render settings for the max runtime.
     *
     * @return array
     */
    protected function returnMaxRuntime(): array
    {
        return [
            // Maximum runtime in seconds, before triggering an emergency break.
            static::VALUE => 60,
            static::RENDER => static::EDITABLE_INPUT,
            static::EVALUATE => static::EVAL_MAX_RUNTIME,
            static::SECTION => static::SECTION_EMERGENCY,
        ];
    }

    /**
     * The kreXX version.
     *
     * @var string
     */
    public $version = '5.0.0';
}
