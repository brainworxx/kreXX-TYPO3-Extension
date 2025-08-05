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
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordPropertyClosure;

/**
 * Retrieve the dynamic getter values of a Domain Record object.
 */
class DomainRecordRetriever implements GetterRetrieverInterface
{
    /**
     * @inheritDoc
     */
    public function canHandle(object $object): bool
    {
        return $object instanceof Record;
    }

    /**
     * @inheritDoc
     */
    public function handle(ReflectionClass $ref): array
    {
        // First stop, we retrieve the properties from the Record object.
        $result = $this->retrieveProperties($ref);

        // Second stop, retrieve everything else from the RawRecord.
        if (!$ref->hasProperty('rawRecord')) {
            // Again: Huh, not what I expected.
            return $result;
        }
        $rawRecordPropertyRef = $ref->getProperty('rawRecord');
        /** @var \TYPO3\CMS\Core\Domain\RawRecord $rawRecord */
        $rawRecord = $ref->retrieveValue($rawRecordPropertyRef);
        $rawRecordRef = new ReflectionClass($rawRecord);
        $result = array_merge(
            $result,
            $this->retrieveRawRecordProperties($rawRecordRef)
        );

        return $result;
    }

    /**
     * Retrieve the properties, the uid and the pid from the RawRecord.
     *
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     *   ReflectionClass of the RawRecord.
     *
     * @return array
     *   The properties of the RawRecord, including uid and pid.
     */
    protected function retrieveRawRecordProperties(ReflectionClass $ref): array
    {
        $properties = $this->retrieveProperties($ref);
        if (!$ref->hasProperty('uid')) {
            $properties['uid'] = $ref->retrieveValue($ref->getProperty('uid'));
        }
        if (!$ref->hasProperty('pid')) {
            $properties['pid'] = $ref->retrieveValue($ref->getProperty('pid'));
        }

        return $properties;
    }

    /**
     * Retrieve 'properties' from the Record object. If it is a
     * RecordPropertyClosure is, then instantiate it.
     *
     * @param \Brainworxx\Krexx\Service\Reflection\ReflectionClass $ref
     * @return array
     */
    protected function retrieveProperties(ReflectionClass $ref): array
    {
        $result = [];

        if (!$ref->hasProperty('properties')) {
            // Huh, not what I expected.
            // This is not a Record object, so we cannot retrieve the properties.
            // But it is the right class?!?
            return [];
        }
        $propertyReflection = $ref->getProperty('properties');
        $ref->retrieveValue($propertyReflection);
        foreach ($ref->retrieveValue($propertyReflection) as $property => $value) {
            if ($value instanceof RecordPropertyClosure) {
                $result[$property] = $value->instantiate();
            } else {
                $result[$property] = $value;
            }
        }
        return $result;
    }
}
