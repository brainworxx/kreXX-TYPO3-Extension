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

/**
 * TYPO3 offers an implementation of a class alias mapper. Sadly, it is not
 * reliable. The worst part of this is that I can not reproduce it's failing.
 * This leaves me with implementing our own compatibility layer. Since I do not
 * know what makes the TYPO3 one fail, I can not rely on anything, but very
 * basics of PHP.
 *
 * I was so happy to get rid of the 4.5 compatibility nightmare. This looks
 * almost as bad as our "autoloader".
 */

/**
 * The old \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper was actually
 * removed in 9.0.0.
 * The new \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper is available in
 * 8.7.0, but incompatible with TYPO3 at that point.
 *
 * @deprecated
 *   Will be removed as soon as we drop TYPO3 8.7 compatibility.
 */
namespace TYPO3\CMS\Fluid\Core\ViewHelper {
    if (class_exists(\TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper::class) === false) {
        class AbstractViewHelper extends \TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper {}
    }
}

/**
 * The Tx_Includekrexx_ViewHelpers_DebugViewHelper was the old 4.5 debug view
 * helper.
 *
 * @deprecated
 *   Will be removed with 4.0.0
 */
namespace {
    class Tx_Includekrexx_ViewHelpers_DebugViewHelper extends Brainworxx\Includekrexx\ViewHelpers\DebugViewHelper {}
}