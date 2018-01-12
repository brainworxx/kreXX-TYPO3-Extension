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

namespace Brainworxx\Krexx\Analyse\Callback\Analyse\Objects;

use Brainworxx\Krexx\Analyse\Model;

/**
 * Object traversable analysis.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 *
 * @uses mixed data
 *   The class we are currently analsysing.
 * @uses string name
 *   The variavble name or key in the parrent object / array where the current
 *   class is stored.
 */
class Traversable extends AbstractObjectAnalysis
{
    /**
     * Checks runtime, memory and nesting level. Then trigger the actual analysis.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        // Check nesting level, memory and runtime.
        $this->pool->emergencyHandler->upOneNestingLevel();
        if ($this->pool->emergencyHandler->checkNesting() === true ||
        $this->pool->emergencyHandler->checkEmergencyBreak() === true) {
            // We will not be doing this one, but we need to get down with our
            // nesting level again.
            $this->pool->emergencyHandler->downOneNestingLevel();
            return '';
        }

        // Do the actual analysis
        return $this->getTeversableData();
    }

    /**
     * Analyses the traversable data.
     *
     * @return string
     *   The generated markup.
     */
    protected function getTeversableData()
    {
        $data = $this->parameters['data'];
        $name = $this->parameters['name'];

        // Add a try to prevent the hosting CMS from doing something stupid.
        try {
            // We need to deactivate the current error handling to
            // prevent the host system to do anything stupid.
                set_error_handler(
                    function () {
                    // Do nothing.
                    }
                );
                $parameter = iterator_to_array($data);
        } catch (\Exception $e) {
            //Restore the previous error handler, and return an empty string.
            restore_error_handler();
            return '';
        }

        // Reactivate whatever error handling we had previously.
        restore_error_handler();

        if (is_array($parameter) === true) {
            // Special Array Access here, resulting in more complicated source
            // generation. So we tell the callback to to that.
            $multiline = true;

            // Normal ArrayAccess, direct access to the array. Nothing special
            if ($data instanceof \ArrayAccess) {
                $multiline = false;
            }

            // SplObject pool use the object as keys, so we need some
            // multiline stuff!
            if ($data instanceof \SplObjectStorage) {
                $multiline = true;
            }

            /** @var Model $model */
            $model = $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                ->setName($name)
                ->setType('Foreach')
                ->addParameter('data', $parameter)
                ->addParameter('multiline', $multiline);

            // Check, if we are handling a huge array. Huge arrays tend to result in a huge
            // output, maybe even triggering a emergency break. t oavoid this, we give them
            // a special callback.
            if (count($parameter) > $this->pool->config->getSetting('arrayCountLimit')) {
                $model->injectCallback(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughLargeArray')
                )->setNormal('Simplified Traversable Info')
                    ->addToJson('Help', $this->pool->messages->getHelp('simpleArray'));
            } else {
                $model->injectCallback(
                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Iterate\\ThroughArray')
                )->setNormal('Traversable Info');
            }

            $result = $this->pool->render->renderExpandableChild($model);
            $this->pool->emergencyHandler->downOneNestingLevel();
            return $result;
        }

        // Still here?!? Return an empty string.
        return '';
    }
}
