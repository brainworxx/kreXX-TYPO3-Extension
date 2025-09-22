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

namespace Brainworxx\Includekrexx\Plugins\FluidDebugger\EventHandlers\GetterRetriever;

use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockGridData;

class GridDataRetriever extends AbstractGetterRetriever
{
    /**
     * @inheritDoc
     */
    public function canHandle(object $object): bool
    {
        return $object instanceof ContentBlockGridData;
    }

    /**
     * Retrieve 'properties' from the Record object. If it is a
     * RecordPropertyClosure is, then instantiate it.
     *
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     *   ReflectionClass of the RawRecord.
     *
     * @return array
     *   The properties of the RawRecord, including uid and pid.
     */
    public function handle(ReflectionClass $ref): array
    {
        if (!$ref->hasProperty('grids')) {
            // Huh, not what I expected.
            // This is not a Grid object, so we cannot retrieve the properties.
            // But it is the right class?!?
            return [];
        }

        return $this->processObjectValues($ref->retrieveValue($ref->getProperty('grids')));
    }
}
