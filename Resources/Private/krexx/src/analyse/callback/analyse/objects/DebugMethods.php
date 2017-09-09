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
 *   kreXX Copyright (C) 2014-2017 Brainworxx GmbH
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

use Brainworxx\Krexx\Analyse\Code\Connectors;

/**
 * Poll all configured debug methods of a class.
 *
 * @package Brainworxx\Krexx\Analyse\Callback\Analyse\Objects
 */
class DebugMethods extends AbstractObjectAnalysis
{

    /**
     * Calls all configured debug methods in die class.
     *
     * I've added a try and an empty error function callback
     * to catch possible problems with this. This will,
     * of cause, not stop a possible fatal in the function
     * itself.
     *
     * @return string
     *   The generated markup.
     */
    public function callMe()
    {
        $data = $this->parameters['data'];

        $output = '';

        foreach (explode(',', $this->pool->config->getSetting('debugMethods')) as $funcName) {
            // Check if:
            // 1.) Method exists
            // 2.) Method can be called
            // 3.) It's not blacklisted.
            if (method_exists($data, $funcName) &&
                is_callable(array($data, $funcName)) &&
                $this->pool->config->isAllowedDebugCall($data, $funcName)
            ) {
                $onlyOptionalParams = true;
                // We need to check if the callable function requires any parameters.
                // We will not call those, because we simply can not provide them.
                /** @var \ReflectionClass $reflectionClass */
                $reflectionClass = $this->parameters['ref'];
                $ref = $reflectionClass->getMethod($funcName);

                /** @var \ReflectionParameter $param */
                foreach ($ref->getParameters() as $param) {
                    if (!$param->isOptional()) {
                        // We've got a required parameter!
                        // We will not call this one.
                        $onlyOptionalParams = false;
                        break;
                    }
                }

                if ($onlyOptionalParams) {
                    // Add a try to prevent the hosting CMS from doing something stupid.
                    try {
                        // We need to deactivate the current error handling to
                        // prevent the host system to do anything stupid.
                        set_error_handler(function () {
                            // Do nothing.
                        });
                        $result = $data->$funcName();
                    } catch (\Exception $e) {
                        // Do nothing.
                    }

                    // Reactivate whatever error handling we had previously.
                    restore_error_handler();

                    if (isset($result)) {
                        $output .= $this->pool->render->renderExpandableChild(
                            $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Model')
                                ->setName($funcName)
                                ->setType('debug method')
                                ->setNormal('. . .')
                                ->setHelpid($funcName)
                                ->setConnectorType(Connectors::METHOD)
                                ->addParameter('data', $result)
                                ->injectCallback(
                                    $this->pool->createClass('Brainworxx\\Krexx\\Analyse\\Callback\\Analyse\\Debug')
                                )
                        );
                        unset($result);
                    }
                }
            }
        }
        return $output;
    }
}
