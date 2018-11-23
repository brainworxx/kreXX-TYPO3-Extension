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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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
 * Configuration fallback settings.
 *
 * We have so much of them, they need an own class.
 *
 * @package Brainworxx\Krexx\Service\Config
 */
class Fallback
{

    const RENDER = 'render';
    const EVALUATE = 'eval';
    const VALUE = 'value';
    const SECTION = 'section';

    const EVAL_BOOL = 'evalBool';
    const EVAL_INT = 'evalInt';
    const EVAL_MAX_RUNTIME = 'evalMaxRuntime';
    const DO_NOT_EVAL = 'doNotEval';
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

    const VALUE_TRUE = 'true';
    const VALUE_FALSE = 'false';
    const VALUE_BROWSER = 'browser';
    const VALUE_FILE = 'file';

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

    /**
     * Defining the layout of the frontend editing form.
     *
     * @var array
     */
    public $configFallback;

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
    protected $editableSelect = array(
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_SELECT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_TRUE,
    );

    /**
     * Render settings for a editable input field.
     *
     * @var array
     */
    protected $editableInput = array(
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_INPUT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_TRUE,
    );

    /**
     * Render settings for a display only input field.
     *
     * @var array
     */
    protected $displayOnlyInput = array(
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_INPUT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    );

    /**
     * Render settings for a display only select field.
     *
     * @var array
     */
    protected $displayOnlySelect = array(
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_SELECT,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    );

    /**
     * Render settings for a field which will not be displayed, or accept values.
     *
     * @var array
     */
    protected $displayNothing = array(
        Fallback::RENDER_TYPE => Fallback::RENDER_TYPE_NONE,
        Fallback::RENDER_EDITABLE => Fallback::VALUE_FALSE,
    );

    /**
     * Here we store all relevant data.
     *
     * @var Pool
     */
    protected $pool;

    /**
     * Injects the pool and initializes the security.
     *
     * @param Pool $pool
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;

        $this->configFallback = array(
            Fallback::SECTION_OUTPUT => array(
                Fallback::SETTING_DISABLED,
                Fallback::SETTING_IP_RANGE,
                Fallback::SETTING_DETECT_AJAX,
            ),
            Fallback::SECTION_BEHAVIOR => array(
                Fallback::SETTING_SKIN,
                Fallback::SETTING_DESTINATION,
                Fallback::SETTING_MAX_FILES,
                Fallback::SETTING_USE_SCOPE_ANALYSIS,
            ),
            Fallback::SECTION_PRUNE => array(
                Fallback::SETTING_MAX_STEP_NUMBER,
                Fallback::SETTING_ARRAY_COUNT_LIMIT,
                Fallback::SETTING_NESTING_LEVEL,
            ),
            Fallback::SECTION_PROPERTIES => array(
                Fallback::SETTING_ANALYSE_PROTECTED,
                Fallback::SETTING_ANALYSE_PRIVATE,
                Fallback::SETTING_ANALYSE_TRAVERSABLE,
            ),
            Fallback::SECTION_METHODS => array(
                Fallback::SETTING_ANALYSE_PROTECTED_METHODS,
                Fallback::SETTING_ANALYSE_PRIVATE_METHODS,
                Fallback::SETTING_ANALYSE_GETTER,
                Fallback::SETTING_DEBUG_METHODS,
            ),
            Fallback::SECTION_EMERGENCY => array(
                Fallback::SETTING_MAX_CALL,
                Fallback::SETTING_MAX_RUNTIME,
                Fallback::SETTING_MEMORY_LEFT,
            ),
        );

        $this->feConfigFallback = array(
            Fallback::SETTING_ANALYSE_PROTECTED_METHODS => array(
                // Analyse protected class methods.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_METHODS,
            ),
            Fallback::SETTING_ANALYSE_PRIVATE_METHODS => array(
                // Analyse private class methods.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_METHODS,
            ),
            Fallback::SETTING_ANALYSE_PROTECTED => array(
                // Analyse protected class properties.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_PROPERTIES,
            ),
            Fallback::SETTING_ANALYSE_PRIVATE => array(
                // Analyse private class properties.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_PROPERTIES,
            ),
            Fallback::SETTING_ANALYSE_TRAVERSABLE => array(
                // Analyse traversable part of classes.
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_PROPERTIES,
            ),
            Fallback::SETTING_DEBUG_METHODS => array(
                // Debug methods that get called.
                // A debug method must be public and have no parameters.
                // Change these only if you know what you are doing.
                Fallback::VALUE => 'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties',
                Fallback::RENDER => $this->displayOnlyInput,
                Fallback::EVALUATE => Fallback::EVAL_DEBUG_METHODS,
                Fallback::SECTION =>  Fallback::SECTION_METHODS,
            ),
            Fallback::SETTING_NESTING_LEVEL => array(
                // Maximum nesting level.
                Fallback::VALUE => 5,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_PRUNE,
            ),
            Fallback::SETTING_MAX_CALL => array(
                // Maximum amount of kreXX calls.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_EMERGENCY,
            ),
            Fallback::SETTING_DISABLED => array(
                // Disable kreXX.
                Fallback::VALUE => Fallback::VALUE_FALSE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_OUTPUT,
            ),
            Fallback::SETTING_DESTINATION => array(
                // Output destination. Either 'file' or 'browser'.
                Fallback::VALUE => Fallback::VALUE_BROWSER,
                Fallback::RENDER => $this->displayOnlySelect,
                Fallback::EVALUATE => Fallback::EVAL_DESTINATION,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ),
            Fallback::SETTING_MAX_FILES => array(
                // Maximum files that are kept inside the logfolder.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->displayOnlyInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ),
            Fallback::SETTING_SKIN => array(
                // Skin for kreXX. We have provided 'hans' and 'smokygrey'.
                Fallback::VALUE => 'smokygrey',
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_SKIN,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ),
            Fallback::SETTING_DETECT_AJAX => array(
                // Try to detect ajax requests.
                // If set to 'true', kreXX is disabled for them.
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_OUTPUT,
            ),
            Fallback::SETTING_IP_RANGE => array(
                // IP range for calling kreXX.
                // kreXX is disabled for everyone who dies not fit into this range.
                Fallback::VALUE => '*',
                Fallback::RENDER => $this->displayNothing,
                Fallback::EVALUATE => Fallback::EVAL_IP_RANGE,
                Fallback::SECTION => Fallback::SECTION_OUTPUT,
            ),
            Fallback::SETTING_DEV_HANDLE => array(
                Fallback::VALUE => '',
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_DEV_HANDLE,
                Fallback::SECTION => ''
            ),
            Fallback::SETTING_ANALYSE_GETTER => array(
                // Analyse the getter methods of a class and try to
                // get a possible return value without calling the method.
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION =>  Fallback::SECTION_METHODS,
            ),
            Fallback::SETTING_MEMORY_LEFT => array(
                // Maximum MB memory left, before triggering an emergency break.
                Fallback::VALUE => 64,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_EMERGENCY,
            ),
            Fallback::SETTING_MAX_RUNTIME => array(
                // Maximum runtime in seconds, before triggering an emergency break.
                Fallback::VALUE => 60,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_MAX_RUNTIME,
                Fallback::SECTION => Fallback::SECTION_EMERGENCY,
            ),
            Fallback::SETTING_USE_SCOPE_ANALYSIS => array(
                // Use the scope analysis (aka auto configuration).
                Fallback::VALUE => Fallback::VALUE_TRUE,
                Fallback::RENDER => $this->editableSelect,
                Fallback::EVALUATE => Fallback::EVAL_BOOL,
                Fallback::SECTION => Fallback::SECTION_BEHAVIOR,
            ),
            Fallback::SETTING_MAX_STEP_NUMBER => array(
                // Maximum step numbers that get analysed from a backtrace.
                // All other steps be be omitted.
                Fallback::VALUE => 10,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_PRUNE,
            ),
            Fallback::SETTING_ARRAY_COUNT_LIMIT => array(
                // Limit for the count in an array. If an array is larger that this,
                // we will use the ThroughLargeArray callback
                Fallback::VALUE => 300,
                Fallback::RENDER => $this->editableInput,
                Fallback::EVALUATE => Fallback::EVAL_INT,
                Fallback::SECTION => Fallback::SECTION_PRUNE
            ),
        );
    }

    /**
     * List of stuff who's fe-editing status can not be changed. Never.
     *
     * @see Tools::evaluateSetting
     *   Evaluating everything in here will fail, meaning that the
     *   setting will not be accepted.
     *
     * @var array
     */
    protected $feConfigNoEdit = array(
        Fallback::SETTING_DESTINATION,
        Fallback::SETTING_MAX_FILES,
        Fallback::SETTING_DEBUG_METHODS,
        Fallback::SETTING_IP_RANGE,
    );

    /**
     * Known Problems with debug functions, which will most likely cause a fatal.
     *
     * @see \Brainworxx\Krexx\Service\Config\Config::isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Service\Plugin\Registration::addMethodToDebugBlacklist()
     *
     * @var array
     */
    protected $methodBlacklist = array();

    /**
     * These classes will never be polled by debug methods, because that would
     * most likely cause a fatal.
     *
     * @see \Brainworxx\Krexx\Service\Config\Security->isAllowedDebugCall()
     * @see \Brainworxx\Krexx\Analyse\Callback\Analyse\Objects->pollAllConfiguredDebugMethods()
     *
     * @var array
     */
    protected $classBlacklist = array(
        // Fun with reflection classes. Not really.
        '\\ReflectionType',
        '\\ReflectionGenerator',
        '\\Reflector',
    );

    /**
     * The kreXX version.
     *
     * @var string
     */
    public $version = '3.0.1';
}
