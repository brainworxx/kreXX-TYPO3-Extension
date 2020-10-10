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

namespace Brainworxx\Krexx\View\Skins\Hans;

/**
 * Interface ConstInterface
 *
 * @deprecated
 *   Since 4.0.0. Will be removed.
 *
 * @package Brainworxx\Krexx\View\Skins\Hans
 */
interface ConstInterface
{
    /**
     * @var string
     */
    const MARKER_NAME = '{name}';

    /**
     * @var string
     */
    const MARKER_NORMAL = '{normal}';

    /**
     * @var string
     */
    const MARKER_CONNECTOR_LEFT = '{connectorLeft}';

    /**
     * @var string
     */
    const MARKER_CONNECTOR_RIGHT = '{connectorRight}';

    /**
     * @var string
     */
    const MARKER_GEN_SOURCE = '{gensource}';

    /**
     * @var string
     */
    const MARKER_VERSION = '{version}';

    /**
     * @var string
     */
    const MARKER_KREXX_COUNT = '{KrexxCount}';

    /**
     * @var string
     */
    const MARKER_HEADLINE = '{headline}';

    /**
     * @var string
     */
    const MARKER_CSS_JS = '{cssJs}';

    /**
     * @var string
     */
    const MARKER_SEARCH = '{search}';

    /**
     * @var string
     */
    const MARKER_MESSAGES = '{messages}';

    /**
     * @var string
     */
    const MARKER_MESSAGE = '{message}';

    /**
     * @var string
     */
    const MARKER_ENCODING = '{encoding}';

    /**
     * @var string
     */
    const MARKER_CONFIG_INFO = '{configInfo}';

    /**
     * @var string
     */
    const MARKER_CALLER = '{caller}';

    /**
     * @var string
     */
    const MARKER_CSS = '{css}';

    /**
     * @var string
     */
    const MARKER_JS = '{js}';

    /**
     * @var string
     */
    const MARKER_DATA = '{data}';

    /**
     * @var string
     */
    const MARKER_SOURCE_BUTTON = '{sourcebutton}';

    /**
     * @var string
     */
    const MARKER_EXPAND = '{expand}';

    /**
     * @var string
     */
    const MARKER_CALLABLE = '{callable}';

    /**
     * @var string
     */
    const MARKER_EXTRA = '{extra}';

    /**
     * @var string
     */
    const MARKER_TYPE = '{type}';

    /**
     * @var string
     */
    const MARKER_TYPE_CLASSES = '{type-classes}';

    /**
     * @var string
     */
    const MARKER_CODE_WRAPPER_LEFT = '{codewrapperLeft}';

    /**
     * @var string
     */
    const MARKER_CODE_WRAPPER_RIGHT = '{codewrapperRight}';

    /**
     * @var string
     */
    const MARKER_K_TYPE = '{ktype}';

    /**
     * @var string
     */
    const MARKER_IS_EXPANDED = '{isExpanded}';

    /**
     * @var string
     */
    const MARKER_NEST = '{nest}';

    /**
     * @var string
     */
    const MARKER_ID = '{id}';

    /**
     * @var string
     */
    const MARKER_VALUE = '{value}';

    /**
     * @var string
     */
    const MARKER_TEXT = '{text}';

    /**
     * @var string
     */
    const MARKER_SELECTED = '{selected}';

    /**
     * @var string
     */
    const MARKER_SOURCE = '{source}';

    /**
     * @var string
     */
    const MARKER_OPTIONS = '{options}';

    /**
     * @var string
     */
    const MARKER_CLASS = '{class}';

    /**
     * @var string
     */
    const MARKER_ERROR_STRING = '{errstr}';

    /**
     * @var string
     */
    const MARKER_FILE = '{file}';

    /**
     * @var string
     */
    const MARKER_LINE = '{line}';

    /**
     * @var string
     */
    const MARKER_CLASS_NAME = '{className}';

    /**
     * @var string
     */
    const MARKER_LINE_NO = '{lineNo}';

    /**
     * @var string
     */
    const MARKER_SOURCE_CODE = '{sourceCode}';

    /**
     * @var string
     */
    const MARKER_PLUGINS = '{plugins}';

    /**
     * @var string
     */
    const MARKER_CALLER_FILE = '{callerFile}';

    /**
     * @var string
     */
    const MARKER_CALLER_LINE = '{callerLine}';

    /**
     * @var string
     */
    const MARKER_CALLER_DATE = '{date}';

    /**
     * @var string
     */
    const MARKER_CALLER_URL = '{callerUrl}';

    /**
     * @var string
     */
    const MARKER_HELP = '{help}';

    /**
     * @var string
     */
    const MARKER_HELP_TITLE = '{helptitle}';

    /**
     * @var string
     */
    const MARKER_HELP_TEXT = '{helptext}';

    /**
     * @var string
     */
    const MARKER_CONNECTOR = '{connector}';

    /**
     * @var string
     */
    const MARKER_KREXX_ID = '{KrexxId}';

    /**
     * @var string
     */
    const MARKER_STYLE = '{style}';

    /**
     * @var string
     */
    const MARKER_MAIN_FUNCTION = '{mainfunction}';

    /**
     * @var string
     */
    const MARKER_DOM_ID = '{domId}';

    /**
     * @var string
     */
    const MARKER_PLUGIN_TEXT = '{plugintext}';

    /**
     * @var string
     */
    const MARKER_PLUGIN_ACTIVE_TEXT = '{activetext}';

    /**
     * @var string
     */
    const MARKER_PLUGIN_ACTIVE_CLASS = '{activeclass}';

    /**
     * @var string
     */
    const MARKER_LANGUAGE = '{language}';

    /**
     * @var string
     */
    const MARKER_ADDITIONAL_JSON = '{addjson}';

    /**
     * @var string
     */
    const MARKER_K_DEBUG_CLASSES = '{kdebug-classes}';

    /**
     * @var string
     */
    const MARKER_K_CONFIG_CLASSES = '{kconfiguration-classes}';

    /**
     * @var string
     */
    const DATA_ATTRIBUTE_JSON = 'addjson';

    /**
     * @var string
     */
    const DATA_ATTRIBUTE_SOURCE = 'source';

    /**
     * @var string
     */
    const DATA_ATTRIBUTE_WRAPPER_R = 'codewrapperRight';

    /**
     * @var string
     */
    const DATA_ATTRIBUTE_WRAPPER_L = 'codewrapperLeft';

    /**
     * @var string
     */
    const FILE_EX_CHILD_NORMAL = 'expandableChildNormal';

    /**
     * @var string
     */
    const FILE_SI_CHILD = 'singleChild';

    /**
     * @var string
     */
    const FILE_SI_CHILD_EX = 'singleChildExtra';

    /**
     * @var string
     */
    const FILE_SI_CHILD_CALL = 'singleChildCallable';

    /**
     * @var string
     */
    const FILE_SOURCE_BUTTON = 'sourcebutton';

    /**
     * @var string
     */
    const FILE_NEST = 'nest';

    /**
     * @var string
     */
    const FILE_BACKTRACE_SOURCELINE = 'backtraceSourceLine';

    /**
     * @var string
     */
    const FILE_CALLER = 'caller';

    /**
     * @var string
     */
    const FILE_HELPROW = 'helprow';

    /**
     * @var string
     */
    const FILE_HELP = 'help';

    /**
     * @var string
     */
    const FILE_CONNECTOR_LEFT = 'connectorLeft';

    /**
     * @var string
     */
    const FILE_CONNECTOR_RIGHT = 'connectorRight';

    /**
     * @var string
     */
    const FILE_SI_PLUGIN = 'singlePlugin';

    /**
     * @var string
     */
    const FILE_SEARCH = 'search';

    /**
     * @var string
     */
    const FILE_RECURSION = 'recursion';

    /**
     * @var string
     */
    const FILE_HEADER = 'header';

    /**
     * @var string
     */
    const FILE_FOOTER = 'footer';

    /**
     * @var string
     */
    const FILE_CSSJS = 'cssJs';

    /**
     * @var string
     */
    const FILE_SI_SELECT_OPTIONS = 'singleSelectOptions';

    /**
     * @var string
     */
    const FILE_SI_EDIT_CHILD = 'singleEditableChild';

    /**
     * @var string
     */
    const FILE_SI_BUTTON = 'singleButton';

    /**
     * @var string
     */
    const FILE_FATAL_MAIN = 'fatalMain';

    /**
     * @var string
     */
    const FILE_FATAL_HEADER = 'fatalHeader';

    /**
     * @var string
     */
    const FILE_MESSAGE = 'message';

    /**
     * @var string
     */
    const FILE_SI_HR = 'singleChildHr';

    /**
     * @var string
     */
    const FILE_BR = 'br';
}
