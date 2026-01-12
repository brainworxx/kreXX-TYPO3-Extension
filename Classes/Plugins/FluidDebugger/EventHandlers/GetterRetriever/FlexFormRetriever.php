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

use Brainworxx\Krexx\Krexx;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use TYPO3\CMS\Core\Domain\FlexFormFieldValues;

/**
 * Retrieving the values from a FlexFormFieldValues object.
 *
 * @deprecated
 *   Will be removed as soon as we drop support for TYPO3 13.
 */
class FlexFormRetriever extends AbstractGetterRetriever
{
    /**
     * We only handle flex form field values
     *
     * TYPO3 14 has the 'getSheets' method, which is visible in the debug output
     * by default.
     * TYPO3 13 does not have this method, so we need to handle it here.
     *
     * @param object $object
     *   The possible flexform fields object.
     *
     * @return bool
     *   TRUE if we can handle the object, FALSE otherwise.
     */
    public function canHandle(object $object): bool
    {
        return $object instanceof FlexFormFieldValues
            && !method_exists($object, 'getSheets');
    }

    /**
     * Retrieve the getters for flex form field values
     *
     * @param ReflectionClass $ref
     *   The reflection class of the object.
     *
     * @return array
     *   The properties we can retrieve.
     */
    public function handle(ReflectionClass $ref): array
    {
        $sheets = [];
        if ($ref->hasProperty('sheets')) {
            $sheets = $ref->retrieveValue($ref->getProperty('sheets'));
        }

        // Now we need to flatten the sheets to a single level array
        // Every key should be unique. And if it is not, that value can not be
        // retrieved, forever breaking the flexform values.
        // When this happens, we must use NULL as value, and inform the developer.
        $result = [];
        foreach ($sheets as $sheet) {
            foreach ($sheet as $fieldKey => $fieldValue) {
                if (array_key_exists($fieldKey, $result)) {
                    // Key already exists, we can not retrieve this value.
                    $result[$fieldKey] = null;
                    Krexx::$pool->messages->addMessage('brokenFlexFormDetected', [$fieldKey]);
                } else {
                    $result[$fieldKey] = $fieldValue;
                }
            }
        }

        return $result;
    }
}
