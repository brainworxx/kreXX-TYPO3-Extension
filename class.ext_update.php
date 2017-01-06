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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * TYPO3 installation class for kreXX
 */
class ext_update
{

    /**
     * Returns whether this script is available in the backend.
     *
     * @return bool
     *   Always TRUE.
     */
    public function access()
    {
        return false;
    }

    /**
     * Main Function.
     *
     * @return string
     *   Gives feedback of what was actually done.
     */
    public function main()
    {
        // We flush the caches, just in case. The extension manager does not flush
        // the ext_localconf cache.
        $this->flushCache();
    }

    /**
     * Does a system cache wipe if available, flushes everything if not.
     */
    protected function flushCache()
    {
        // 1. Get the TCE Main.
        // 't3lib_TCEmain' => 'TYPO3\\CMS\\Core\\DataHandling\\DataHandler',
        if ((int)TYPO3_version < 6) {
            // Oldschool cache management in globals.
            $cacheManager = $GLOBALS['typo3CacheManager'];
        } else {
            $cacheManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager');
        }

        // 2. Flush the cache.
        if (method_exists($cacheManager, 'flushCachesInGroup')) {
            // TYPO3 6+
            $cacheManager->flushCachesInGroup('system');
        } else {
            // TYPO3 4.x
            $cacheManager->flushCaches();
        }

    }
}
