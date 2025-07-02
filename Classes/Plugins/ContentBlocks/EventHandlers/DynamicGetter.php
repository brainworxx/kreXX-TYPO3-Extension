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

namespace Brainworxx\Includekrexx\Plugins\ContentBlocks\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Callback\CallbackConstInterface;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;
use Brainworxx\Krexx\Service\Reflection\ReflectionClass;
use TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData;

/**
 * ContentBlocks uses dynamic getters in fluid.
 *
 * @see \TYPO3\CMS\ContentBlocks\DataProcessing\ContentBlockData::get()
 * @event \Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::callMe::start
 */
class DynamicGetter implements EventHandlerInterface, CallbackConstInterface
{
    /**
     * The resource pool
     *
     * @var Pool
     */
    protected Pool $pool;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * Adding the results from the dynamic getter to the output.
     *
     * @param \Brainworxx\Krexx\Analyse\Callback\AbstractCallback|null $callback
     *   The original callback. Or null, when coming from the processing.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   There is no model available at the start event.
     *
     * @return string
     *   The generated markup.
     */
    public function handle(?AbstractCallback $callback = null, ?Model $model = null): string
    {
        /** @var ReflectionClass $ref */
        $ref = $callback->getParameters()[static::PARAM_REF];
        $data = $ref->getData();

        if (!$data instanceof ContentBlockData) {
            return '';
        }

        $result = $this->retrieveGetterArray($ref);
        // @todo: Iterate through the result and feed it to the renderer

        return '';
    }

    protected function retrieveGetterArray(ReflectionClass $ref): array
    {
        $result = [];
        if ($ref->hasProperty('_processed')) {
            $result = $ref->retrieveValue($ref->getProperty('_processed'));
        }
        if ($ref->hasProperty('_name')) {
            $result['_name'] = $ref->retrieveValue($ref->getProperty('_name'));
        }
        if ($ref->hasProperty('_grids')) {
            $result['_grids'] = $ref->retrieveValue($ref->getProperty('_grids'));
        }

        // @todo: Add the record data to the result array.
        if ($ref->hasProperty('_record')) {
            $record = $ref->retrieveValue($ref->getProperty('_record'));
        }
        return $result;
    }
}
