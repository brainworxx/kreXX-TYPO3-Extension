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

namespace Brainworxx\Includekrexx\ViewHelpers;

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper as AbstractViewHelperCms;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper as AbstractViewHelperFluid;

/**
 * Thanks to Helmut Hummel for this solution.
 *
 * The class alias map works well in TYPO3 old school installations.
 * When using it in Composer Mode, things are a little bit different.
 * Some copy pasta from Slack:
 * > The class alias maps in Composer mode are read before dumping the
 * > autoloader. This means that the class exists call will be non functional,
 * > as the class will never exist at this point.
 *
 * @deprecated
 *   Will be removed as soon as we drop 8.7 Support.
 */
if (class_exists(AbstractViewHelperCms::class) === true) {
    class ComptibilityViewHelper extends AbstractViewHelperCms
    {

    }
} else {
    class ComptibilityViewHelper extends AbstractViewHelperFluid
    {

    }
}
