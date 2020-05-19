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

use Brainworxx\Krexx\Analyse\ConstInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Plugin\SettingsGetter;
use Brainworxx\Krexx\View\Skins\RenderHans;
use Brainworxx\Krexx\View\Skins\RenderSmokyGrey;

/**
 * Configuration fallback settings.
 *
 * We have so much of them, they need an own class.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
abstract class Fallback implements ConstInterface, ConfigConstInterface
{

    /**
     * Defining the layout of the frontend editing form.
     *
     * @var array
     */
    public $configFallback = [
        Fallback::SECTION_OUTPUT => [
            Fallback::SETTING_DISABLED,
            Fallback::SETTING_IP_RANGE,
            Fallback::SETTING_DETECT_AJAX,
        ],
        Fallback::SECTION_BEHAVIOR =>[
            Fallback::SETTING_SKIN,
            Fallback::SETTING_DESTINATION,
            Fallback::SETTING_MAX_FILES,
            Fallback::SETTING_USE_SCOPE_ANALYSIS,
        ],
        Fallback::SECTION_PRUNE => [
            Fallback::SETTING_MAX_STEP_NUMBER,
            Fallback::SETTING_ARRAY_COUNT_LIMIT,
            Fallback::SETTING_NESTING_LEVEL,
        ],
        Fallback::SECTION_PROPERTIES => [
            Fallback::SETTING_ANALYSE_PROTECTED,
            Fallback::SETTING_ANALYSE_PRIVATE,
            Fallback::SETTING_ANALYSE_TRAVERSABLE,
        ],
        Fallback::SECTION_METHODS => [
            Fallback::SETTING_ANALYSE_PROTECTED_METHODS,
            Fallback::SETTING_ANALYSE_PRIVATE_METHODS,
            Fallback::SETTING_ANALYSE_GETTER,
            Fallback::SETTING_DEBUG_METHODS,
        ],
        Fallback::SECTION_EMERGENCY => [
            Fallback::SETTING_MAX_CALL,
            Fallback::SETTING_MAX_RUNTIME,
            Fallback::SETTING_MEMORY_LEFT,
        ],
    ];

    /**
     * Values, rendering settings and the actual fallback value.
     *
     * @var array
     */
    public $feConfigFallback = [];

    /**
     * Render settings for a editable select field.
     *
     * @var array
     */
    protected $editableSelect = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_SELECT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_TRUE,
    ];

    /**
     * Render settings for a editable input field.
     *
     * @var array
     */
    protected $editableInput = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_INPUT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_TRUE,
    ];

    /**
     * Render settings for a display only input field.
     *
     * @var array
     */
    protected $displayOnlyInput = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_INPUT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    ];

    /**
     * Render settings for a display only select field.
     *
     * @var array
     */
    protected $displayOnlySelect = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_SELECT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    ];

    /**
     * Render settings for a field which will not be displayed, or accept values.
     *
     * @var array
     */
    protected $displayNothing = [
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_NONE,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    ];

    /**
     * The skin configuration.
     *
     * @var array
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

        $this->feConfigFallback = [
            Fallback::SETTING_ANALYSE_PROTECTED_METHODS => $this->returnBoolSelectFalse(Fallback::SECTION_METHODS),
            Fallback::SETTING_ANALYSE_PRIVATE_METHODS => $this->returnBoolSelectFalse(Fallback::SECTION_METHODS),
            Fallback::SETTING_ANALYSE_PROTECTED => $this->returnBoolSelectFalse(Fallback::SECTION_PROPERTIES),
            Fallback::SETTING_ANALYSE_PRIVATE => $this->returnBoolSelectFalse(Fallback::SECTION_PROPERTIES),
            Fallback::SETTING_ANALYSE_TRAVERSABLE => $this->returnBoolSelectTrue(Fallback::SECTION_PROPERTIES),
            Fallback::SETTING_DEBUG_METHODS => $this->returnDebugMethods(),
            Fallback::SETTING_NESTING_LEVEL => $this->returnInput(Fallback::SECTION_PRUNE, 5),
            Fallback::SETTING_MAX_CALL => $this->returnInput(Fallback::SECTION_EMERGENCY, 10),
            Fallback::SETTING_DISABLED => $this->returnBoolSelectFalse(Fallback::SECTION_OUTPUT),
            Fallback::SETTING_DESTINATION => $this->returnDestination(),
            Fallback::SETTING_MAX_FILES => $this->returnMaxFiles(),
            Fallback::SETTING_SKIN => $this->returnSkin(),
            Fallback::SETTING_DETECT_AJAX => $this->returnBoolSelectTrue(Fallback::SECTION_OUTPUT),
            Fallback::SETTING_IP_RANGE => $this->returnIpRange(),
            Fallback::SETTING_DEV_HANDLE => $this->returnDevHandle(),
            Fallback::SETTING_ANALYSE_GETTER => $this->returnBoolSelectTrue(Fallback::SECTION_METHODS),
            Fallback::SETTING_MEMORY_LEFT => $this->returnInput(Fallback::SECTION_EMERGENCY, 64),
            Fallback::SETTING_MAX_RUNTIME => $this->returnMaxRuntime(),
            Fallback::SETTING_USE_SCOPE_ANALYSIS => $this->returnBoolSelectTrue(Fallback::SECTION_BEHAVIOR),
            Fallback::SETTING_MAX_STEP_NUMBER => $this->returnInput(Fallback::SECTION_PRUNE, 10),
            Fallback::SETTING_ARRAY_COUNT_LIMIT => $this->returnInput(Fallback::SECTION_PRUNE, 300),
        ];

        // Setting up out two bundled skins.
        $this->generateSkinConfiguration();
    }

    /**
     * Generate the skin configuration.
     */
    protected function generateSkinConfiguration()
    {
        $this->skinConfiguration = array_merge(
            [
                Fallback::SKIN_SMOKY_GREY => [
                    Fallback::SKIN_CLASS => RenderSmokyGrey::class,
                    Fallback::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/smokygrey/'
                ],
                Fallback::SKIN_HANS => [
                    Fallback::SKIN_CLASS => RenderHans::class,
                    Fallback::SKIN_DIRECTORY => KREXX_DIR . 'resources/skins/hans/'
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
    protected function returnBoolSelectFalse($section)
    {
        return [
            Fallback::VALUE => Fallback::VALUE_FALSE,
            Fallback::RENDER => $this->editableSelect,
            Fallback::EVALUATE => Fallback::EVAL_BOOL,
            Fallback::SECTION => $section,
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
    protected function returnBoolSelectTrue($section)
    {
        return [
            Fallback::VALUE => Fallback::VALUE_TRUE,
            Fallback::RENDER => $this->editableSelect,
            Fallback::EVALUATE => Fallback::EVAL_BOOL,
            Fallback::SECTION => $section,
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
    protected function returnInput($section, $value)
    {
        return [
            Fallback::VALUE => $value,
            Fallback::RENDER => $this->editableInput,
            Fallback::EVALUATE => Fallback::EVAL_INT,
            Fallback::SECTION => $section,
        ];
    }

    /**
     * The render settings for the debug methods.
     *
     * @return array
     */
    protected function returnDebugMethods()
    {
        return [
            // Debug methods that get called.
            // A debug method must be public and have no parameters.
            // Change these only if you know what you are doing.
            Fallback::VALUE => Fallback::VALUE_DEBUG_METHODS,
            Fallback::RENDER => $this->displayOnlyInput,
            Fallback::EVALUATE => Fallback::EVAL_DEBUG_METHODS,
            Fallback::SECTION =>  Fallback::SECTION_METHODS,
        ];
    }

    /**
     * The render settings for the destination.
     *
     * @return array
     */
    protected function returnDestination()
    {
        return [
                // Either 'file' or 'browser'.
                Fallback::VALUE => Fallback::VALUE_BROWSER,
                Fallback::RENDER => $this->displayOnlySelect,
                Fallback::EVALUATE => Fallback::EVAL_DESTINATION,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ];
    }

    /**
     * The render settings for the max files.
     *
     * @return array
     */
    protected function returnMaxFiles()
    {
        return [
            // Maximum files that are kept inside the logfolder.
            Fallback::VALUE => 10,
            Fallback::RENDER => $this->displayOnlyInput,
            Fallback::EVALUATE => Fallback::EVAL_INT,
            Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
        ];
    }

    /**
     * The render settings for the skin.
     *
     * @return array
     */
    protected function returnSkin()
    {
        return [
            Fallback::VALUE => Fallback::SKIN_SMOKY_GREY,
            Fallback::RENDER => $this->editableSelect,
            Fallback::EVALUATE => Fallback::EVAL_SKIN,
            Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
        ];
    }

    /**
     * The render settings for the ip range.
     *
     * @return array
     */
    protected function returnIpRange()
    {
        return [
            // IP range for calling kreXX.
            // kreXX is disabled for everyone who dies not fit into this range.
            Fallback::VALUE => '*',
            Fallback::RENDER => $this->displayNothing,
            Fallback::EVALUATE => Fallback::EVAL_IP_RANGE,
            Fallback::SECTION => Fallback::SECTION_OUTPUT,
        ];
    }

    /**
     * The render settings for the dev handle.
     *
     * @return array
     */
    protected function returnDevHandle()
    {
        return [
            Fallback::VALUE => '',
            Fallback::RENDER => $this->editableInput,
            Fallback::EVALUATE => Fallback::EVAL_DEV_HANDLE,
            Fallback::SECTION => ''
        ];
    }

    /**
     * The render settings for the max runtime.
     *
     * @return array
     */
    protected function returnMaxRuntime()
    {
        return [
            // Maximum runtime in seconds, before triggering an emergency break.
            Fallback::VALUE => 60,
            Fallback::RENDER => $this->editableInput,
            Fallback::EVALUATE => Fallback::EVAL_MAX_RUNTIME,
            Fallback::SECTION => Fallback::SECTION_EMERGENCY,
        ];
    }

    /**
     * The kreXX version.
     *
     * @var string
     */
    public $version = '3.2.4 dev';
}
