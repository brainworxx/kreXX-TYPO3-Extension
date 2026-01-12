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
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\RecordInterface;

/**
 * Retrieve the dynamic getter values of a DomainPage object.
 * But only in TYPO3 14.0 and beyond.
 *
 * @codeCoverageIgnore
 *   We ignore th coveraage of this class untill TYPO3 14 becomes our main target.
 *   We do test it, but we only upload coverage for TYPO3 13.
 */
class DomainPageRetriever extends AbstractGetterRetriever
{
    /**
     * {@inheritDoc}
     */
    public function canHandle(object $object): bool
    {
        return $object instanceof Page && $object instanceof RecordInterface;
    }

    /**
     * Retrieve all the values that this object knows.
     *
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     * @return array
     * @throws \ReflectionException
     */
    public function handle(ReflectionClass $ref): array
    {
        /** @var array $properties */
        $properties = $ref->retrieveValue($ref->getProperty('properties'));
        /** @var \TYPO3\CMS\Core\Domain\RawRecord $rawRecord */
        $rawRecord = $ref->retrieveValue($ref->getProperty('rawRecord'));
        /** @var array $specialProperties */
        $specialProperties = $ref->retrieveValue($ref->getProperty('specialProperties'));

        if (!isset($properties['uid'])) {
            $properties['uid'] = $rawRecord->getUid();
        }

        if (!isset($properties['pid'])) {
            $properties['pid'] = $rawRecord->getPid();
        }

        return array_merge($specialProperties, $properties);
    }
}
