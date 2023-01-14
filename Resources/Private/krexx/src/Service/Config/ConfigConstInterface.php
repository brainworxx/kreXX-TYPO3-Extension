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
 *   kreXX Copyright (C) 2014-2023 Brainworxx GmbH
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

/**
 * Constants for the configuration.
 *
 * Some code scanners really dislike literals for the configuration.
 * And to avoid bad ratings we use constants and overly complicated logic
 * to stitch together the fallback configuration.
 * These are the constants.
 */
interface ConfigConstInterface
{
    /**
     * Array key. Containing the render info of one of the configurations.
     *
     * @var string
     */
    public const RENDER = 'render';

    /**
     * Array key. Containing the name of the evaluation method inside the
     * Validation class.
     *
     * @see
     *   EVAL_BOOL
     *   EVAL_INT
     *   EVAL_MAX_RUNTIME
     *   EVAL_DESTINATION
     *   EVAL_SKIN
     *   EVAL_IP_RANGE
     *
     * @var string
     */
    public const EVALUATE = 'eval';

    /**
     * Array kay. Containing the actual value.
     *
     * @var string
     */
    public const VALUE = 'value';

    /**
     * Array Key. Containing the section name used for better readability.
     *
     * @var string
     */
    public const SECTION = 'section';

    /**
     * Array key. Contains the config of the output section.
     *
     * @var string
     */
    public const SECTION_OUTPUT = 'output';

    /**
     * Array key. Contains the config of the behavior section.
     *
     * @var string
     */
    public const SECTION_BEHAVIOR = 'behavior';

    /**
     * Array key. Contains the config of the prune section.
     *
     * @var string
     */
    public const SECTION_PRUNE = 'prune';

    /**
     * Array key. Contains the config of the properties section.
     *
     * @var string
     */
    public const SECTION_PROPERTIES = 'properties';

    /**
     * Array key. Contains the config of the methods section.
     *
     * @var string
     */
    public const SECTION_METHODS = 'methods';

    /**
     * Array key. Contains the config of the emergency section.
     *
     * @var string
     */
    public const SECTION_EMERGENCY = 'emergency';

    /**
     * Array key. Contains the config of the feEditing section.
     *
     * @var string
     */
    public const SECTION_FE_EDITING = 'feEditing';

    /**
     * Dropdown value. Human-readable form of a true boolean.
     *
     * @var string
     */
    public const VALUE_TRUE = 'true';

    /**
     * Dropdown value. Human-readable form of a false boolean.
     *
     * @var string
     */
    public const VALUE_FALSE = 'false';

    /**
     * Dropdown value. Output destination browser during the php shutdown phase.
     *
     * @var string
     */
    public const VALUE_BROWSER = 'browser';

    /**
     * Dropdown value. Output destination file logging.
     *
     * @var string
     */
    public const VALUE_FILE = 'file';

    /**
     * Dropdown value. Output destination browser immediately.
     *
     * @var string
     */
    public const VALUE_BROWSER_IMMEDIATELY = 'browserImmediately';

    /**
     * "Preconfigured" debug methods.
     * @var string
     */
    public const VALUE_DEBUG_METHODS =
        'debug,__toArray,toArray,__toString,toString,_getProperties,__debugInfo,getProperties';

    /**
     * Array key. Holds the value of disabled.
     *
     * @var string
     */
    public const SETTING_DISABLED = 'disabled';

    /**
     * Array key. Holds the value of iprange.
     *
     * @var string
     */
    public const SETTING_IP_RANGE = 'iprange';

    /**
     * Array key. Holds the value of skin.
     *
     * @var string
     */
    public const SETTING_SKIN = 'skin';

    /**
     * Array key. Holds the value of destination.
     *
     * @var string
     */
    public const SETTING_DESTINATION = 'destination';

    /**
     * Array key. Holds the value of maxfiles.
     *
     * @var string
     */
    public const SETTING_MAX_FILES = 'maxfiles';

    /**
     * Array key. Holds the value of detectAjax.
     *
     * @var string
     */
    public const SETTING_DETECT_AJAX = 'detectAjax';

    /**
     * Array key. Holds the value of level.
     *
     * @var string
     */
    public const SETTING_NESTING_LEVEL = 'level';

    /**
     * Array key. Holds the value of maxCall.
     *
     * @var string
     */
    public const SETTING_MAX_CALL = 'maxCall';

    /**
     * Array key. Holds the value of maxRuntime.
     *
     * @var string
     */
    public const SETTING_MAX_RUNTIME = 'maxRuntime';

    /**
     * Array key. Holds the value of memoryLeft.
     *
     * @var string
     */
    public const SETTING_MEMORY_LEFT = 'memoryLeft';

    /**
     * Array key. Holds the value of useScopeAnalysis.
     *
     * @deprecated since 5.0.0 Will be removed
     *   The setting for the scope analysis was removed.
     *
     * @var string
     */
    public const SETTING_USE_SCOPE_ANALYSIS = 'useScopeAnalysis';

    /**
     * The language keys for the language file.
     *
     * @var string
     */
    public const SETTING_LANGUAGE_KEY = 'languageKey';

    /**
     * Array key. Holds the value of analyseProtected.
     *
     * @var string
     */
    public const SETTING_ANALYSE_PROTECTED = 'analyseProtected';

    /**
     * Array key. Holds the value of analysePrivate.
     *
     * @var string
     */
    public const SETTING_ANALYSE_PRIVATE = 'analysePrivate';

    /**
     * Array key. Holds the value of analyseScalar.
     *
     * @var string
     */
    public const SETTING_ANALYSE_SCALAR = 'analyseScalar';

    /**
     * Array key. Holds the value of analyseTraversable.
     *
     * @var string
     */
    public const SETTING_ANALYSE_TRAVERSABLE = 'analyseTraversable';

    /**
     * Array key. Holds the value of analyseProtectedMethods.
     *
     * @var string
     */
    public const SETTING_ANALYSE_PROTECTED_METHODS = 'analyseProtectedMethods';

    /**
     * Array key. Holds the value of analysePrivateMethods.
     *
     * @var string
     */
    public const SETTING_ANALYSE_PRIVATE_METHODS = 'analysePrivateMethods';

    /**
     * Array key. Holds the value of analyseGetter.
     *
     * @var string
     */
    public const SETTING_ANALYSE_GETTER = 'analyseGetter';

    /**
     * Array key. Holds the value of debugMethods.
     *
     * @var string
     */
    public const SETTING_DEBUG_METHODS = 'debugMethods';

    /**
     * Array key. Holds the value of maxStepNumber.
     *
     * @var string
     */
    public const SETTING_MAX_STEP_NUMBER = 'maxStepNumber';

    /**
     * Array key. Holds the value of arrayCountLimit.
     *
     * @var string
     */
    public const SETTING_ARRAY_COUNT_LIMIT = 'arrayCountLimit';

    /**
     * Array key. Holds the render type a single setting, who is also the
     * template file name.
     *
     * @see
     *   RENDER_TYPE_SELECT
     *   RENDER_TYPE_INPUT
     *   RENDER_TYPE_NONE
     *
     * @var string
     */
    public const RENDER_TYPE = 'Type';

    /**
     * Identifies this configuration as editable in the frontend.
     *
     * @var string
     */
    public const RENDER_EDITABLE = 'Editable';

    /**
     * Renders a select dropdown.
     *
     * @var string
     */
    public const RENDER_TYPE_SELECT = 'Select';

    /**
     * Renders a text input field.
     *
     * @var string
     */
    public const RENDER_TYPE_INPUT = 'Input';

    /**
     * Renders nothing. no output at all.
     *
     * @var string
     */
    public const RENDER_TYPE_NONE = 'None';

    /**
     * Value from the configuration file.
     *
     * Tells the renderer, that this value will be displayed in the FE and can
     * be edited.
     *
     * @var string
     */
    public const RENDER_TYPE_CONFIG_FULL = 'full';

    /**
     * Value from the configuration file.
     *
     * Tells the renderer, that this value will be displayed in the FE.
     * kreXX will not accept values for this configuration.
     *
     * @var string
     */
    public const RENDER_TYPE_CONFIG_DISPLAY = 'display';

    /**
     * Value from the configuration file.
     *
     * Tells the renderer, that this value will not be displayed in the FE.
     * kreXX will not accept values for this configuration.
     *
     * @var string
     */
    public const RENDER_TYPE_CONFIG_NONE = 'none';

    /**
     * Class name that renders a registered skin.
     *
     * @var string
     */
    public const SKIN_CLASS = 'class';

    /**
     * Directory with the template files of a registered skin.
     *
     * @var string
     */
    public const SKIN_DIRECTORY = 'dir';
}
