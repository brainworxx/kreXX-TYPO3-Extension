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
 *   kreXX Copyright (C) 2014-2018 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Code\Connectors;

/**
 * Analysing the getter methods, without the actual 'get' word in the method name.
 */
class Tx_Includekrexx_Rewrite_AnalysisCallbackIterateTroughGetter extends ThroughGetter
{

    /**
     * Iterating through a list of reflection methods.
     *
     * Change: we remove the 'get' from the name, since fluid requires this.
     *
     * @param array $methodList
     *   The list of methods we are going through, consisting of \ReflectionMethod
     *
     * @return string
     *   The generated DOM.
     */
    protected function goThroughMethodList(array $methodList)
    {
        $output = '';

        /** @var \ReflectionMethod $reflectionMethod */
        foreach ($methodList as $reflectionMethod) {
            // Back to level 0, we reset the deep counter.
            $this->deep = 0;

            // Now we have three possible outcomes:
            // 1.) We have an actual value
            // 2.) We got NULL as a value
            // 3.) We were unable to get any info at all.
            $comments = nl2br($this->commentAnalysis->getComment($reflectionMethod, $this->parameters['ref']));

            /** @var Model $model */
            $methodName = $reflectionMethod->getName();
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName(lcfirst(substr($methodName, strlen($this->currentPrefix))))
                ->addToJson('method comment', $comments)
                ->addToJson('method name', $methodName . '()');

            // We need to decide if we are handling static getters.
            if ($reflectionMethod->isStatic() === true) {
                $model->setConnectorType(Connectors::STATIC_METHOD);
            } else {
                $model->setConnectorType(Connectors::METHOD);
            }

            // Get ourselves a possible return value
            $output .= $this->retrievePropertyValue($reflectionMethod, $model);
        }

        return $this->handleDataviewerEav() . $output;
    }

    /**
     * If we are facing a \MageDeveloper\Dataviewer\Domain\Model\Record,
     * we may want to take a look at the containing dynamic getter values.
     *
     * @return string
     *   The generated HTML markup for the magic getters.
     */
    protected function handleDataviewerEav()
    {
        $record = $this->parameters['data'];
        $output = '';
        if (is_object($record) && is_a($record, '\\MageDeveloper\\Dataviewer\\Domain\\Model\\Record')) {
            try {
                /** @var \MageDeveloper\Dataviewer\Domain\Model\Record $record  */
                $values = $record->getValues();

                foreach ($record->getDatatype()->getFields() as $field) {
                    /** @var \Brainworxx\Krexx\Analyse\Model $model */
                    if (is_a($field, '\\MageDeveloper\\Dataviewer\\Domain\\Model\\Field') === false) {
                        // Huh, not what I was expecting. We skip this one.
                        continue;
                    }

                    // Get the value
                    $record->initializeValue($field);
                    $code = $field->getCode();
                    $value = $values->getValueByCode($code)->getValue();

                    // Send a new model to the analysis hub.
                    $output .= $this->pool
                        ->routing->analysisHub($this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                            ->setName($code . '.value')
                            ->setConnectorType(Connectors::METHOD)
                            ->setData($value)
                            ->addToJson('hint', 'Magic dataviewer getter method.'));
                }
            } catch (\Exception $e) {
                // Something whent wrong here.
                // We skip the output here.
                return '';
            }

            // Add a HR to the output, for better readability.
            $output .= $this->pool->render->renderSingeChildHr();
        }

        return $output;
    }
}
