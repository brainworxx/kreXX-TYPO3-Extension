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

declare(strict_types=1);

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use ArrayAccess;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughArray;
use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughLargeArray;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Config\ConfigConstInterface;
use SplObjectStorage;
use Throwable;

/**
 * Object traversable analysis.
 *
 * @uses mixed data
 *   The class we are currently analysing.
 * @uses string name
 *   The variable name or key in the parent object / array where the current
 *   class is stored.
 */
class Traversable extends AbstractObjectAnalysis implements ConfigConstInterface
{
    /**
     * Checks runtime, memory and nesting level. Then trigger the actual analysis.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe(): string
    {
        $output = $this->dispatchStartEvent();

        // Check nesting level, memory and runtime.
        $this->pool->emergencyHandler->upOneNestingLevel();
        if (
            $this->pool->emergencyHandler->checkNesting() ||
            $this->pool->emergencyHandler->checkEmergencyBreak()
        ) {
            // We will not be doing this one, but we need to get down with our
            // nesting level again.
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $output;
        }

        // Do the actual analysis
        return $output . $this->retrieveTraversableData();
    }

    /**
     * Analyses the traversable data.
     *
     * @return string
     *   The generated markup.
     */
    protected function retrieveTraversableData(): string
    {
        $data = $this->parameters[static::PARAM_DATA];


        // Add a try to prevent the hosting CMS from doing something stupid.
        try {
            // We need to deactivate the current error handling to
            // prevent the host system to do anything stupid.
            set_error_handler(
                function (): void {
                    // Do nothing.
                }
            );
            $parameter = iterator_to_array($data);
        } catch (Throwable $e) {
            //Restore the previous error handler, and return an empty string.
            restore_error_handler();
            $this->pool->emergencyHandler->downOneNestingLevel();
            return '';
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();
        return $this->analyseTraversableResult($data, $parameter);
    }

    /**
     * Analyse the traversable retrieval result.
     *
     * @param object $originalClass
     *   The class that we are analysing.
     * @param array $result
     *   The retrieved traversable result.
     *
     * @return string
     *   The rendered HTML.
     */
    protected function analyseTraversableResult($originalClass, array $result): string
    {
        // Direct access to the iterator object,de depending on the object itself.
        $multiline = !($originalClass instanceof ArrayAccess)
            || $originalClass instanceof SplObjectStorage;
        $messages = $this->pool->messages;

        /** @var Model $model */
        $model = $this->pool->createClass(Model::class)
            ->setName($this->parameters[static::PARAM_NAME])
            ->setType(static::TYPE_FOREACH)
            ->addParameter(static::PARAM_DATA, $result)
            ->addParameter(static::PARAM_MULTILINE, $multiline)
            ->addToJson($messages->getHelp('metaLength'), (string)count($result));

        // Check, if we are handling a huge array. Huge arrays tend to result in a huge
        // output, maybe even triggering an emergency break. to avoid this, we give them
        // a special callback.
        if (count($result) > (int) $this->pool->config->getSetting(static::SETTING_ARRAY_COUNT_LIMIT)) {
            $model->injectCallback($this->pool->createClass(ThroughLargeArray::class))
                ->setNormal($messages->getHelp('simplifiedTraversableInfo'))
                ->setHelpid('simpleArray');
        } else {
            $model->injectCallback($this->pool->createClass(ThroughArray::class))
                ->setNormal($messages->getHelp('traversableInfo'));
        }

        $analysisResult = $this->pool->render->renderExpandableChild(
            $this->dispatchEventWithModel(static::EVENT_MARKER_ANALYSES_END, $model)
        );

        $this->pool->emergencyHandler->downOneNestingLevel();
        return $analysisResult;
    }
}
