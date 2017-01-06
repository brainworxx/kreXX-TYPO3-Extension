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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

// The main problem with 7.0 is, that compatibility6 may or may not be installed.
// If not, I have to put his thing here, hoping not to break anything!
if (!class_exists('Tx_Fluid_Core_ViewHelper_AbstractViewHelper')) {
    /**
     * Class Tx_Fluid_Core_ViewHelper_AbstractViewHelper
     */
    abstract class Tx_Fluid_Core_ViewHelper_AbstractViewHelper extends AbstractViewHelper
    {
    }
}
// For some reasons, TYPO3 7.6 manages to load this file multiple times, causing
// a fatal.
if (class_exists('Tx_Includekrexx_ViewHelpers_DebugViewHelper')) {
    return;
}

/**
 * Class Tx_Includekrexx_ViewHelpers_DebugViewHelper
 *
 * In case that anybody is actually reading this:
 * Right now, this is just a proof of concept.
 *
 * If that you are really desperate, use it. It gives the actual PHP
 * stuff inside the template. Most of this stuff is not reachable from fluid,
 * so we will implement a filter later on.
 *
 * @see https://github.com/brainworxx/kreXX-TYPO3-Extension/issues/4
 * @see https://forge.typo3.org/issues/72950
 *
 * @usage
 *   {namespace krexx=Tx_Includekrexx_ViewHelpers}
 *   <krexx:debug>{_all}</krexx:debug>
 *   or
 *   <krexx:debug value="{my: 'value', to: 'analyse'}" />
 *   Use this part if you don't want fluid to escape your string or if you are
 *   stitching together an array.
 *   Be aware, that this does NOT work in 8.5 and beyond, because the namespace
 *   declaration is different.
 */
class Tx_Includekrexx_ViewHelpers_DebugViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper
{

    /**
     * {@inheritdoc}
     */
    public function initializeArguments()
    {
        $this->registerArgument('value', 'mixed', 'The variable we want to analyse.', false);
    }

    /**
     * A wrapper for kreXX();
     *
     * @todo
     *   Analyse rendering context for additional data.
     *
     * @return string
     *   Returns an empty string.
     */
    public function render()
    {
        $found  = false;
        if (!is_null($this->arguments['value'])) {
            krexx($this->arguments['value']);
            $found = true;
        }

        $children = $this->renderChildren();
        if (!is_null($children)) {
            krexx($children);
            $found = true;
        }

        if (!$found) {
            // Both are NULL, we must tell the dev!
            krexx(null);
        }

        return '';
    }
}
