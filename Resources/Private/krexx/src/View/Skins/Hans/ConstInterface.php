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

namespace Brainworxx\Krexx\View\Skins\Hans;

interface ConstInterface
{
    const MARKER_NAME = '{name}';
    const MARKER_NORMAL = '{normal}';
    const MARKER_CONNECTOR_LEFT = '{connectorLeft}';
    const MARKER_CONNECTOR_RIGHT = '{connectorRight}';
    const MARKER_GEN_SOURCE = '{gensource}';
    const MARKER_VERSION = '{version}';
    const MARKER_KREXX_COUNT = '{KrexxCount}';
    const MARKER_HEADLINE = '{headline}';
    const MARKER_CSS_JS = '{cssJs}';
    const MARKER_SEARCH = '{search}';
    const MARKER_MESSAGES = '{messages}';
    const MARKER_MESSAGE = '{message}';
    const MARKER_ENCODING = '{encoding}';
    const MARKER_CONFIG_INFO = '{configInfo}';
    const MARKER_CALLER = '{caller}';
    const MARKER_CSS = '{css}';
    const MARKER_JS = '{js}';
    const MARKER_DATA = '{data}';
    const MARKER_SOURCE_BUTTON = '{sourcebutton}';
    const MARKER_EXPAND = '{expand}';
    const MARKER_CALLABLE = '{callable}';
    const MARKER_EXTRA = '{extra}';
    const MARKER_TYPE = '{type}';
    const MARKER_TYPE_CLASSES = '{type-classes}';
    const MARKER_CODE_WRAPPER_LEFT = '{codewrapperLeft}';
    const MARKER_CODE_WRAPPER_RIGHT = '{codewrapperRight}';
    const MARKER_K_TYPE = '{ktype}';
    const MARKER_IS_EXPANDED = '{isExpanded}';
    const MARKER_NEST = '{nest}';
    const MARKER_ID = '{id}';
    const MARKER_VALUE = '{value}';
    const MARKER_TEXT = '{text}';
    const MARKER_SELECTED = '{selected}';
    const MARKER_SOURCE = '{source}';
    const MARKER_OPTIONS = '{options}';
    const MARKER_CLASS = '{class}';
    const MARKER_ERROR_STRING = '{errstr}';
    const MARKER_FILE = '{file}';
    const MARKER_LINE = '{line}';
    const MARKER_CLASS_NAME = '{className}';
    const MARKER_LINE_NO = '{lineNo}';
    const MARKER_SOURCE_CODE = '{sourceCode}';
    const MARKER_PLUGINS = '{plugins}';

    const MARKER_CALLER_FILE = '{callerFile}';
    const MARKER_CALLER_LINE = '{callerLine}';
    const MARKER_CALLER_DATE = '{date}';
    const MARKER_CALLER_URL = '{callerUrl}';
    const MARKER_HELP = '{help}';
    const MARKER_HELP_TITLE = '{helptitle}';
    const MARKER_HELP_TEXT = '{helptext}';
    const MARKER_CONNECTOR = '{connector}';
    const MARKER_KREXX_ID = '{KrexxId}';
    const MARKER_STYLE = '{style}';
    const MARKER_MAIN_FUNCTION = '{mainfunction}';
    const MARKER_DOM_ID = '{domId}';
    const MARKER_PLUGIN_TEXT = '{plugintext}';
    const MARKER_PLUGIN_ACTIVE_TEXT = '{activetext}';
    const MARKER_PLUGIN_ACTIVE_CLASS = '{activeclass}';

    const DATA_ATTRIBUTE_SOURCE = 'source';
    const DATA_ATTRIBUTE_WRAPPER_R = 'codewrapperRight';
    const DATA_ATTRIBUTE_WRAPPER_L = 'codewrapperLeft';

    const FILE_EX_CHILD_NORMAL = 'expandableChildNormal';
    const FILE_SI_CHILD = 'singleChild';
    const FILE_SI_CHILD_EX = 'singleChildExtra';
    const FILE_SI_CHILD_CALL = 'singleChildCallable';
    const FILE_SOURCE_BUTTON = 'sourcebutton';
    const FILE_NEST = 'nest';
    const FILE_BACKTRACE_SOURCELINE = 'backtraceSourceLine';
    const FILE_CALLER = 'caller';
    const FILE_HELPROW = 'helprow';
    const FILE_HELP = 'help';
    const FILE_CONNECTOR_LEFT = 'connectorLeft';
    const FILE_CONNECTOR_RIGHT = 'connectorRight';
    const FILE_SI_PLUGIN = 'singlePlugin';
    const FILE_SEARCH = 'search';
    const FILE_RECURSION = 'recursion';
    const FILE_HEADER = 'header';
    const FILE_FOOTER = 'footer';
    const FILE_CSSJS = 'cssJs';
    const FILE_SI_SELECT_OPTIONS = 'singleSelectOptions';
    const FILE_SI_EDIT_CHILD = 'singleEditableChild';
    const FILE_SI_BUTTON = 'singleButton';
    const FILE_FATAL_MAIN = 'fatalMain';
    const FILE_FATAL_HEADER = 'fatalHeader';
    const FILE_MESSAGE = 'message';
    const FILE_SI_HR = 'singleChildHr';
    const FILE_BR = 'br';
}
