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
 *   kreXX Copyright (C) 2014-2020 Brainworxx GmbH
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
 * Constants for the configuration
 *
 * @package Brainworxx\Krexx\Service\Config
 */
interface ConfigConstInterface
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
    const SETTING_ANALYSE_SCALAR = 'analyseScalar';
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
    // The render type is also part of the template filename of the cookie editor.
    const RENDER_TYPE_SELECT = 'Select';
    const RENDER_TYPE_INPUT = 'Input';
    const RENDER_TYPE_NONE = 'None';

    const RENDER_TYPE_INI_FULL = 'full';
    const RENDER_TYPE_INI_DISPLAY = 'display';
    const RENDER_TYPE_INI_NONE = 'none';

    const SKIN_SMOKY_GREY = 'smokygrey';
    const SKIN_HANS = 'hans';
}
