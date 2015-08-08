<?php

/**
 * @file
 *   Debug viewhelper to use kreXX in fluid templates
 *   kreXX: Krumo eXXtended
 *
 *   kreXX is a debugging tool, which displays structured information
 *   about any PHP object. It is a nice replacement for print_r() or var_dump()
 *   which are used by a lot of PHP developers.
 *
 *   kreXX is a fork of Krumo, which was originally written by:
 *   Kaloyan K. Tsvetkov <kaloyan@kaloyan.info>
 *
 * @author brainworXX GmbH <info@brainworxx.de>
 *
 * @license http://opensource.org/licenses/LGPL-2.1
 *   GNU Lesser General Public License Version 2.1
 *
 *   kreXX Copyright (C) 2014-2015 Brainworxx GmbH
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

// The mainproblem with 7.0 is, that compatibility6 may or may not be installed.
// If not, I have to put his thing here, hoping not to break anything!
if (!class_exists('Tx_Fluid_Core_ViewHelper_AbstractViewHelper')) {
  abstract class Tx_Fluid_Core_ViewHelper_AbstractViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {}
}

/**
 * Class Tx_Includekrexx_ViewHelpers_DebugViewHelper
 *
 * In case that anybody is actually reading this:
 * Right now, this is just a proof of concept, but we've got plans for it.
 * The <f:debug> could be a lot better, because it does not show everything
 * accessible inside the fluid template. Getter functions in classes which
 * are not declared via TCA will not show up, but can be polled for data.
 * Also, it contains a debug-output (just like kreXX ;-) ) which may be
 * confusing for people.
 *
 * In case that you are really desperate, use it. It gives the actual PHP
 * stuff inside the template. Most of this stuff is not reachable from fluid,
 * so we will implement a filter later on.
 *
 * @usage
 *   {namespace krexx=Tx_Includekrexx_ViewHelpers}
 *   <krexx:debug>{_all}</krexx:debug>
 */
class Tx_Includekrexx_ViewHelpers_DebugViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

  /**
   * A wrapper for kreXX();
   *
   * @return string
   *   Returns an empty string.
   */
  public function render() {
    krexx($this->renderChildren());
    return '';
  }
}