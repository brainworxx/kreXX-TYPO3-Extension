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

namespace Brainworxx\Includekrexx\Plugins\FluidDataViewer\EventHandlers;

use Brainworxx\Krexx\Analyse\Callback\AbstractCallback;
use Brainworxx\Krexx\Analyse\Code\Connectors;
use Brainworxx\Krexx\Analyse\Model;
use Brainworxx\Krexx\Service\Factory\EventHandlerInterface;
use Brainworxx\Krexx\Service\Factory\Pool;

/**
 * We simply add the data viewer eav to the output.
 *
 * @event
 *   Brainworxx\Krexx\Analyse\Callback\Iterate\ThroughGetter::callMe::start
 * @package Brainworxx\Includekrexx\Plugins\FluidDataViewer
 */
class AddAnalysis implements EventHandlerInterface
{
    /**
     * The resource pool
     *
     * @var Pool
     */
    protected $pool;

    /**
     * {@inheritdoc}
     */
    public function __construct(Pool $pool)
    {
        $this->pool = $pool;
    }

    /**
     * @param AbstractCallback $params
     *   The calling class.
     * @param \Brainworxx\Krexx\Analyse\Model|null $model
     *   The model, if available
     *
     * @return string
     *   The generated markup.
     */
    public function handle(AbstractCallback $callback, Model $model = null)
    {
        $params = $callback->getParameters();
        $record = $params['data'];
        $output = '';
        if (is_object($record) && is_a($record, '\\MageDeveloper\\Dataviewer\\Domain\\Model\\Record')) {
            try {
                /** @var \MageDeveloper\Dataviewer\Domain\Model\Record $record  */
                $values = $record->getValues();
                /** @var \MageDeveloper\Dataviewer\Domain\Model\Field $field */
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
                // Something went wrong here.
                // We skip the output here.
                return '';
            }

            // Add a HR to the output, for better readability.
            $output .= $this->pool->render->renderSingeChildHr();
        }

        return $output;
    }
}
