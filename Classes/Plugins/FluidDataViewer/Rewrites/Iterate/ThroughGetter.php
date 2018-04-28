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

namespace Brainworxx\Includekrexx\Plugins\FluidDataViewer\Rewrites\Iterate;

use Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter as OrgThroughGetter;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Analyse\Code\Connectors;

/**
 * Analysing the getter methods, without the actual 'get' word in the method name.
 *
 * @package Brainworxx\Includekrexx\Rewrite\Analyse\Callback\Iterate
 */
class ThroughGetter extends OrgThroughGetter
{

    /**
     * {@inheritdoc}
     *
     * We simply add the data viewer eav to the output.
     *
     * @param array $methodList
     *   The list of methods we are going through, consisting of \ReflectionMethod
     *
     * @return string
     *   The generated DOM.
     */
    protected function goThroughMethodList(array $methodList)
    {
        return $this->handleDataviewerEav() . parent::goThroughMethodList($methodList);
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
