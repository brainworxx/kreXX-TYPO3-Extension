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

namespace Brainworxx\Krexx\View\Skins;

use Brainworxx\Krexx\View\AbstractRender;
use Brainworxx\Krexx\View\RenderInterface;
use Brainworxx\Krexx\View\Skins\Hans\BacktraceSourceLine;
use Brainworxx\Krexx\View\Skins\Hans\Button;
use Brainworxx\Krexx\View\Skins\Hans\ConnectorLeft;
use Brainworxx\Krexx\View\Skins\Hans\ConnectorRight;
use Brainworxx\Krexx\View\Skins\Hans\ConstInterface;
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
use Brainworxx\Krexx\View\Skins\Hans\SingleChild;
use Brainworxx\Krexx\View\Skins\Hans\SingleEditableChild;

/**
 * Individual render class for the Hans skin.
 *
 * @package Brainworxx\Krexx\View\Hans
 */
class RenderHans extends AbstractRender implements RenderInterface, ConstInterface
{
    use Recursion;
    use Header;
    use Footer;
    use CssJs;
    use SingleChild;
    use ExpandableChild;
    use SingleEditableChild;
    use Button;
    use FatalMain;
    use FatalHeader;
    use Messages;
    use BacktraceSourceLine;
    use SingeChildHr;
    use Linebreak;
    use PluginList;
    use Help;
    use ConnectorLeft;
    use ConnectorRight;
    use Search;
}
