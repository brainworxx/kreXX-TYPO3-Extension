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
 *   kreXX Copyright (C) 2014-2022 Brainworxx GmbH
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

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(
    function () {
        if (class_exists(\Brainworxx\Includekrexx\Bootstrap\Bootstrap::class)) {
            try {
                \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \Brainworxx\Includekrexx\Bootstrap\Bootstrap::class
                )->checkVersionNumber('5.0.0')
                    ->run();
            } catch (\Throwable $exception) {
                // Do nothing.
                // When updating the extension via ExtensionManager, there is a
                // big chance that the cache is not cleared. And that means that
                // the part above may not work anymore. Hence, we need to make
                // sure that the user does not brick the system.
            }
        }
    }
);
