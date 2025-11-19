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
 *   kreXX Copyright (C) 2014-2025 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Modules;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Brainworxx\Includekrexx\Collectors\LogfileList;

$version = GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();

/**
 * This is so evil.
 *
 * But . . . TYPO3 loads all files for some reason.
 * Adding this to the ignore list in the Sevice.yaml does not help.
 *
 * I literally have no idea how to keep this compatible with both TYPO3 v13 and
 * v14+ without this hack.
 *
 * So, if anybody actually reads this and knows how to do this properly:
 * Please, give me a ticket on GitHub or contact me via TYPO3 Slack.
 */
if ($version > 13) {
    class Log extends AbstractLog
    {
        /**
         * Retrieve the file list.
         *
         * @param \Psr\Http\Message\ServerRequestInterface $request
         *   The frontend request, which is currently not used.
         * @param \Psr\Http\Message\ResponseInterface $response
         *   A response interface, currently not used.
         *
         * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
         *
         * @return \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData
         *   The data we will assign to the admin panel.
         */
        public function getDataToStore(ServerRequestInterface $request, ResponseInterface $response): ModuleData
        {
            return new ModuleData(
                ['files' => GeneralUtility::makeInstance(LogfileList::class)->retrieveFileList()]
            );
        }
    }
} else {
    class Log extends AbstractLog
    {
        /**
         * Retrieve the file list.
         *
         * @param \Psr\Http\Message\ServerRequestInterface $request
         *   The frontend request, which is currently not used.
         *
         * @throws \TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException
         *
         * @return \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData
         *   The data we will assign to the admin panel.
         */
        public function getDataToStore(ServerRequestInterface $request): ModuleData
        {
            return new ModuleData(
                ['files' => GeneralUtility::makeInstance(LogfileList::class)->retrieveFileList()]
            );
        }
    }
}
