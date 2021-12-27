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

namespace Brainworxx\Krexx\View\Skins;

use Brainworxx\Krexx\Analyse\Caller\BacktraceConstInterface;
use Brainworxx\Krexx\Analyse\Code\CodegenConstInterface;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use Brainworxx\Krexx\Service\Plugin\PluginConstInterface;
use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\RenderInterface;
use Brainworxx\Krexx\View\Skins\Hans\BacktraceSourceLine;
use Brainworxx\Krexx\View\Skins\Hans\Button;
use Brainworxx\Krexx\View\Skins\Hans\ConnectorLeft;
use Brainworxx\Krexx\View\Skins\Hans\ConnectorRight;
use Brainworxx\Krexx\View\Skins\Hans\CssJs;
use Brainworxx\Krexx\View\Skins\Hans\ExpandableChild;
use Brainworxx\Krexx\View\Skins\Hans\FatalHeader;
use Brainworxx\Krexx\View\Skins\Hans\FatalMain;
use Brainworxx\Krexx\View\Skins\Hans\Footer;
use Brainworxx\Krexx\View\Skins\Hans\Header;
use Brainworxx\Krexx\View\Skins\Hans\Help;
use Brainworxx\Krexx\View\Skins\Hans\Linebreak;
use Brainworxx\Krexx\View\Skins\Hans\Messages;
use Brainworxx\Krexx\View\Skins\Hans\PluginList;
use Brainworxx\Krexx\View\Skins\Hans\Recursion;
use Brainworxx\Krexx\View\Skins\Hans\Search;
use Brainworxx\Krexx\View\Skins\Hans\SingeChildHr;
use Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild;

/**
 * Individual render class for the Hans skin.
 */
class RenderHans extends AbstractRender implements
    RenderInterface,
    BacktraceConstInterface,
    PluginConstInterface,
    ConfigConstInterface,
    CodegenConstInterface
{
    use BacktraceSourceLine;
    use Button;
    use ConnectorLeft;
    use ConnectorRight;
    use CssJs;
    use ExpandableChild;
    use FatalHeader;
    use FatalMain;
    use Footer;
    use Header;
    use Help;
    use Linebreak;
    use Messages;
    use PluginList;
    use Recursion;
    use Search;
    use SingeChildHr;
    use SingleEditableChild;

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_EX_CHILD_NORMAL = 'expandableChildNormal';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_CHILD = 'singleChild';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_CHILD_EX = 'singleChildExtra';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_CHILD_CALL = 'singleChildCallable';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SOURCE_BUTTON = 'sourcebutton';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_NEST = 'nest';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_BACKTRACE_SOURCELINE = 'backtraceSourceLine';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_CALLER = 'caller';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_HELPROW = 'helprow';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_HELP = 'help';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_CONNECTOR_LEFT = 'connectorLeft';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_CONNECTOR_RIGHT = 'connectorRight';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_PLUGIN = 'singlePlugin';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SEARCH = 'search';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_RECURSION = 'recursion';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_HEADER = 'header';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_FOOTER = 'footer';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_CSSJS = 'cssJs';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_SELECT_OPTIONS = 'singleSelectOptions';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_EDIT_CHILD = 'singleEditableChild';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_BUTTON = 'singleButton';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_FATAL_MAIN = 'fatalMain';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_FATAL_HEADER = 'fatalHeader';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_MESSAGE = 'message';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_SI_HR = 'singleChildHr';

    /**
     * Template file name.
     *
     * @var string
     */
    protected const FILE_BR = 'br';

    /**
     * Data attribute for source generation.
     *
     * @var string
     */
    protected const DATA_ATTRIBUTE_SOURCE = 'source';

    /**
     * Data attribute for source generation.
     *
     * @var string
     */
    protected const DATA_ATTRIBUTE_WRAPPER_R = 'codewrapperRight';

    /**
     * Data attribute for source generation.
     *
     * @var string
     */
    protected const DATA_ATTRIBUTE_WRAPPER_L = 'codewrapperLeft';
}
