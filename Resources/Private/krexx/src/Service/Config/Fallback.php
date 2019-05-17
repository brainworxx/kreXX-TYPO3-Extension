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
abstract class Fallback implements ConstInterface
{
    const RENDER = 'render';
    const EVALUATE = 'eval';
    const VALUE = 'value';
    const SECTION = 'section';

    const EVAL_BOOL = 'evalBool';
    const EVAL_INT = 'evalInt';
    const EVAL_MAX_RUNTIME = 'evalMaxRuntime';
    const EVAL_DESTINATION = 'evalDestination';
    const EVAL_SKIN = 'evalSkin';
    const EVAL_IP_RANGE = 'evalIpRange';
    const EVAL_DEV_HANDLE = 'evalDevHandle';
    const EVAL_DEBUG_METHODS = 'evalDebugMethods';

    const SECTION_OUTPUT = 'output';
    const SECTION_BEHAVIOR = 'behavior';
    const SECTION_PRUNE = 'prune';
    const SECTION_PROPERTIES = 'properties';
    const SECTION_METHODS = 'methods';
    const SECTION_EMERGENCY = 'emergency';
    const SECTION_FE_EDITING = 'feEditing';

    const VALUE_TRUE = 'true';
    const VALUE_FALSE = 'false';
    const VALUE_BROWSER = 'browser';
    const VALUE_FILE = 'file';
    const VALUE_DEBUG_METHODS = 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties';

    const SETTING_DISABLED = 'disabled';
    const SETTING_IP_RANGE = 'iprange';
    const SETTING_SKIN = 'skin';
    const SETTING_DESTINATION = 'destination';
    const SETTING_MAX_FILES = 'maxfiles';
    const SETTING_DETECT_AJAX = 'detectAjax';
    const SETTING_NESTING_LEVEL = 'level';
    const SETTING_MAX_CALL = 'maxCall';
    const SETTING_MAX_RUNTIME = 'maxRuntime';
    const SETTING_MEMORY_LEFT = 'memoryLeft';
    const SETTING_USE_SCOPE_ANALYSIS = 'useScopeAnalysis';
    const SETTING_ANALYSE_PROTECTED = 'analyseProtected';
    const SETTING_ANALYSE_PRIVATE = 'analysePrivate';
    const SETTING_ANALYSE_TRAVERSABLE = 'analyseTraversable';
    const SETTING_ANALYSE_PROTECTED_METHODS = 'analyseProtectedMethods';
    const SETTING_ANALYSE_PRIVATE_METHODS = 'analysePrivateMethods';
    const SETTING_ANALYSE_GETTER = 'analyseGetter';
    const SETTING_DEBUG_METHODS = 'debugMethods';
    const SETTING_MAX_STEP_NUMBER = 'maxStepNumber';
    const SETTING_ARRAY_COUNT_LIMIT = 'arrayCountLimit';
    const SETTING_DEV_HANDLE = 'devHandle';

    const RENDER_TYPE = 'Type';
    const RENDER_EDITABLE = 'Editable';
    // The render type is also part of the template filename of the
    // cookie editor.
    const RENDER_TYPE_SELECT = 'Select';
    const RENDER_TYPE_INPUT = 'Input';
    const RENDER_TYPE_NONE = 'None';

    const RENDER_TYPE_INI_FULL = 'full';
    const RENDER_TYPE_INI_DISPLAY = 'display';
    const RENDER_TYPE_INI_NONE = 'none';

    const SKIN_SMOKY_GREY = 'smokygrey';
    const SKIN_HANS = 'hans';

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
    public $feConfigFallback;

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
            static::SETTING_ANALYSE_PROTECTED_METHODS => [
                // Analyse protected class methods.
                static::VALUE => static::VALUE_FALSE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_METHODS,
            ],
            static::SETTING_ANALYSE_PRIVATE_METHODS => [
                // Analyse private class methods.
                static::VALUE => static::VALUE_FALSE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_METHODS,
            ],
            static::SETTING_ANALYSE_PROTECTED => [
                // Analyse protected class properties.
                static::VALUE => static::VALUE_FALSE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_PROPERTIES,
            ],
            static::SETTING_ANALYSE_PRIVATE => [
                // Analyse private class properties.
                static::VALUE => static::VALUE_FALSE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_PROPERTIES,
            ],
            static::SETTING_ANALYSE_TRAVERSABLE => [
                // Analyse traversable part of classes.
                static::VALUE => static::VALUE_TRUE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_PROPERTIES,
            ],
            static::SETTING_DEBUG_METHODS => [
                // Debug methods that get called.
                // A debug method must be public and have no parameters.
                // Change these only if you know what you are doing.
                static::VALUE => static::VALUE_DEBUG_METHODS,
                static::RENDER => $this->displayOnlyInput,
                static::EVALUATE => static::EVAL_DEBUG_METHODS,
                static::SECTION =>  static::SECTION_METHODS,
            ],
            static::SETTING_NESTING_LEVEL => [
                // Maximum nesting level.
                static::VALUE => 5,
                static::RENDER => $this->editableInput,
                static::EVALUATE => static::EVAL_INT,
                static::SECTION => static::SECTION_PRUNE,
            ],
            static::SETTING_MAX_CALL => [
                // Maximum amount of kreXX calls.
                static::VALUE => 10,
                static::RENDER => $this->editableInput,
                static::EVALUATE => static::EVAL_INT,
                static::SECTION => static::SECTION_EMERGENCY,
            ],
            static::SETTING_DISABLED => [
                // Disable kreXX.
                static::VALUE => static::VALUE_FALSE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_OUTPUT,
            ],
            static::SETTING_DESTINATION => [
                // Output destination. Either 'file' or 'browser'.
                static::VALUE => static::VALUE_BROWSER,
                static::RENDER => $this->displayOnlySelect,
                static::EVALUATE => static::EVAL_DESTINATION,
                static::SECTION => static::SECTION_BEHAVIOR,
            ],
            static::SETTING_MAX_FILES => [
                // Maximum files that are kept inside the logfolder.
                static::VALUE => 10,
                static::RENDER => $this->displayOnlyInput,
                static::EVALUATE => static::EVAL_INT,
                static::SECTION => static::SECTION_BEHAVIOR,
            ],
            static::SETTING_SKIN => [
                static::VALUE => static::SKIN_SMOKY_GREY,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_SKIN,
                static::SECTION => static::SECTION_BEHAVIOR,
            ],
            static::SETTING_DETECT_AJAX => [
                // Try to detect ajax requests.
                // If set to 'true', kreXX is disabled for them.
                static::VALUE => static::VALUE_TRUE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_OUTPUT,
            ],
            static::SETTING_IP_RANGE => [
                // IP range for calling kreXX.
                // kreXX is disabled for everyone who dies not fit into this range.
                static::VALUE => '*',
                static::RENDER => $this->displayNothing,
                static::EVALUATE => static::EVAL_IP_RANGE,
                static::SECTION => static::SECTION_OUTPUT,
            ],
            static::SETTING_DEV_HANDLE => [
                static::VALUE => '',
                static::RENDER => $this->editableInput,
                static::EVALUATE => static::EVAL_DEV_HANDLE,
                static::SECTION => ''
            ],
            static::SETTING_ANALYSE_GETTER => [
                // Analyse the getter methods of a class and try to
                // get a possible return value without calling the method.
                static::VALUE => static::VALUE_TRUE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION =>  static::SECTION_METHODS,
            ],
            static::SETTING_MEMORY_LEFT => [
                // Maximum MB memory left, before triggering an emergency break.
                static::VALUE => 64,
                static::RENDER => $this->editableInput,
                static::EVALUATE => static::EVAL_INT,
                static::SECTION => static::SECTION_EMERGENCY,
            ],
            static::SETTING_MAX_RUNTIME => [
                // Maximum runtime in seconds, before triggering an emergency break.
                static::VALUE => 60,
                static::RENDER => $this->editableInput,
                static::EVALUATE => static::EVAL_MAX_RUNTIME,
                static::SECTION => static::SECTION_EMERGENCY,
            ],
            static::SETTING_USE_SCOPE_ANALYSIS => [
                // Use the scope analysis (aka auto configuration).
                static::VALUE => static::VALUE_TRUE,
                static::RENDER => $this->editableSelect,
                static::EVALUATE => static::EVAL_BOOL,
                static::SECTION => static::SECTION_BEHAVIOR,
            ],
            static::SETTING_MAX_STEP_NUMBER => [
                // Maximum step numbers that get analysed from a backtrace.
                // All other steps be be omitted.
                static::VALUE => 10,
                static::RENDER => $this->editableInput,
                static::EVALUATE => static::EVAL_INT,
                static::SECTION => static::SECTION_PRUNE,
            ],
            static::SETTING_ARRAY_COUNT_LIMIT => [
                // Limit for the count in an array. If an array is larger that this,
                // we will use the ThroughLargeArray callback
                static::VALUE => 300,
                static::RENDER => $this->editableInput,
                static::EVALUATE => static::EVAL_INT,
                static::SECTION => static::SECTION_PRUNE
            ],
        ];

        // Setting up out two bundled skins.
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
     * The kreXX version.
     *
     * @var string
     */
    public $version = '3.1.0 dev';
}
