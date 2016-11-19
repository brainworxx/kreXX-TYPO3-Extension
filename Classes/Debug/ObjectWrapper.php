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
 *   kreXX Copyright (C) 2014-2016 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Debug;

/**
 * Wrapper around the kreXX debug method to use inside the $GLOBALS['error']
 *
 * @package Brainworxx\Includekrexx\Debug
 */
class ObjectWrapper
{
    /**
     * Simple wrapper for kreXX, for useage via debug();
     *
     * Depending on the typo3 version:
     * @see t3lib/config_default.php
     * @see sysext/core/Resources/PHP/GlobalDebugFunction->debug()
     * @see sysext/core/Classes/Core/GlobalDebugFunction->debug()
     *
     * @param mixed $variable
     *   The varialbe we are analysing
     * @param string $name
     *   The name of the variable will be determined by the caller finder class.
     * @param string|integer $line
     *   The line number of the call will be determined by the caller finder class.
     * @param string $file
     *   The file of the call will be determined by the caller finder class.
     * @param string|integer $recursiveDepth
     *   kreXX does not have a recursive depth. It does have a nesting level
     *   which is determined in the configuration. Recursions of objects are
     *   handeled via JS (copy and paste the result). Recursions of arrays
     *   are handeled via the nesting level.
     * @param string|integer $debugLevel
     *   I'm not really sure, what this variable may contain. The standard value
     *   is 'E_DEBUG'. It is not used in the core anywhere and I was not able
     *   to find any documentation about it.
     */
    public function debug($variable, $name, $line, $file, $recursiveDepth, $debugLevel)
    {
        if ($debugLevel !== 'E_DEBUG') {
            // Do nothing, just to be sure.
            return;
        }

        // We need to change the finder serarch pattern to 'debug',
        // because the actual debug call was 'debug($something)'.
        // This way we can make sure, that the source generation will work
        // correctly.
        $oldPattern = \Krexx::$storage->callerFinder->getPattern();
        \Krexx::$storage->callerFinder->setPattern('debug');
        // We ignore the first finding of the pattern.

        // Calling the actual analysis. The pattern from above will be reset
        // afterwards.
        \Krexx::open($variable);

        // Resetting to what it was before.
        \Krexx::$storage->callerFinder->setPattern($oldPattern);
    }
}