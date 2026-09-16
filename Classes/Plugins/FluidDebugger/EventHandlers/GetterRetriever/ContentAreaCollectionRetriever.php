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
 *   kreXX Copyright (C) 2014-2026 Brainworxx GmbH
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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever;

use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use Throwable;
use TYPO3\CMS\Core\Page\ContentAreaClosure;
use TYPO3\CMS\Core\Page\ContentAreaCollection;

/**
 * Retrieve the dynamic getter values of a ContentAreaCollection object.
 */
class ContentAreaCollectionRetriever extends AbstractGetterRetriever
{
    /**
     * {@inheritDoc}
     */
    protected array $handlingClasses = [ContentAreaCollection::class];

    /**
     * {@inheritDoc}
     */
    public function handle(ReflectionClass $ref): array
    {
        // This one is rather easy.
        if (!$ref->hasProperty('contentAreas') || !$ref->hasProperty('request')) {
            // Not what I was expecting, so I can't handle it.
            return [];
        }

        try {
            return iterator_to_array($ref->getData());
        } catch (Throwable) {
            // Something went wrong, so I can't handle it.
            return [];
        }
    }
}