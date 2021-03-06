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

namespace Brainworxx\Includekrexx\Controller;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Old config controller, which only clears the cache.
 *
 * @package Brainworxx\Includekrexx\Controller
 */
class ConfigController extends ActionController
{
    /**
     * As of TYPO3 9.0 there is no way to automatically clear the cache
     * when updating an extension.
     *
     * The editAction is the old standard action of the old 3.0.1 backend
     * module. When this one gets called, we clear the cache and redirect to
     * the new controller
     *
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function editAction()
    {
        $this->objectManager->get(CacheManager::class)
            ->flushCachesInGroup('system');

        $this->redirect('index', 'Index');
    }
}
